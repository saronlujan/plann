<?php

use App\Enums\PlanSlug;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\SyncStripeSubscriptions;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Tenant}
 */
function syncFixture(string $email, ?string $stripeId = 'cus_test_1'): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    $tenant->forceFill(['stripe_id' => $stripeId])->save();

    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => $email]);

    return [$user, $tenant];
}

/**
 * A trimmed Stripe subscription payload, shaped like the real one.
 *
 * @return array<string, mixed>
 */
function stripeSubscription(string $priceId, string $status = 'active', array $overrides = []): array
{
    return array_merge([
        'id' => 'sub_test_1',
        'status' => $status,
        'metadata' => [],
        'trial_end' => null,
        'cancel_at_period_end' => false,
        'current_period_end' => null,
        'items' => [
            'data' => [
                [
                    'id' => 'si_test_1',
                    'quantity' => 1,
                    'price' => ['id' => $priceId, 'product' => 'prod_test_1'],
                ],
            ],
        ],
    ], $overrides);
}

test('mirroring a paid subscription unlocks the app', function () {
    [$user, $tenant] = syncFixture('sync-unlock@example.com');

    // Trial already lapsed: only a real subscription can open the door.
    $tenant->forceFill(['trial_ends_at' => now()->subDay()])->save();

    expect($tenant->subscribed())->toBeFalse();

    app(SyncStripeSubscriptions::class)->mirror($tenant, stripeSubscription('price_pro'));

    expect($tenant->fresh()->subscribed())->toBeTrue();

    actingAs($user)->get('/')->assertSuccessful();
});

test('mirroring is idempotent', function () {
    [, $tenant] = syncFixture('sync-idempotent@example.com');

    $sync = app(SyncStripeSubscriptions::class);
    $payload = stripeSubscription('price_pro');

    $sync->mirror($tenant, $payload);
    $sync->mirror($tenant, $payload);

    // The webhook and the checkout return both run this: it must not duplicate.
    expect($tenant->subscriptions()->count())->toBe(1);
    expect($tenant->subscriptions()->first()->items()->count())->toBe(1);
});

test('the plan follows the purchased price', function () {
    [, $tenant] = syncFixture('sync-plan@example.com');

    Plan::factory()->create(['slug' => PlanSlug::Pro->value, 'stripe_price_id' => 'price_pro']);

    expect($tenant->plan_slug)->toBe(PlanSlug::Basic);

    app(SyncStripeSubscriptions::class)->mirror($tenant, stripeSubscription('price_pro'));

    expect($tenant->fresh()->plan_slug)->toBe(PlanSlug::Pro);
});

test('a paid subscription ends the card-free trial', function () {
    [, $tenant] = syncFixture('sync-trial@example.com');

    expect($tenant->trial_ends_at)->not->toBeNull();

    app(SyncStripeSubscriptions::class)->mirror($tenant, stripeSubscription('price_pro'));

    // Leaving the generic trial in place would keep granting access after the
    // subscription is later cancelled.
    expect($tenant->fresh()->trial_ends_at)->toBeNull();
});

test('a trialing subscription carries its trial end date', function () {
    [, $tenant] = syncFixture('sync-trialing@example.com');

    $trialEnd = now()->addDays(7)->startOfSecond();

    app(SyncStripeSubscriptions::class)->mirror($tenant, stripeSubscription(
        'price_pro',
        'trialing',
        ['trial_end' => $trialEnd->timestamp],
    ));

    $subscription = $tenant->subscriptions()->first();

    expect($subscription->stripe_status)->toBe('trialing');
    expect($subscription->trial_ends_at->timestamp)->toBe($trialEnd->timestamp);
    expect($tenant->fresh()->subscribed())->toBeTrue();
});

test('a subscription set to cancel keeps access until the period ends', function () {
    [$user, $tenant] = syncFixture('sync-grace@example.com');

    $periodEnd = now()->addDays(10)->startOfSecond();

    app(SyncStripeSubscriptions::class)->mirror($tenant, stripeSubscription(
        'price_pro',
        'active',
        ['cancel_at_period_end' => true, 'current_period_end' => $periodEnd->timestamp],
    ));

    $subscription = $tenant->subscriptions()->first();

    expect($subscription->ends_at->timestamp)->toBe($periodEnd->timestamp);
    expect($subscription->onGracePeriod())->toBeTrue();

    // Already paid for: the app stays open until the period actually ends.
    actingAs($user)->get('/')->assertSuccessful();
});

test('a cancelled subscription no longer unlocks the app', function () {
    [$user, $tenant] = syncFixture('sync-cancelled@example.com');
    $tenant->forceFill(['trial_ends_at' => now()->subDay()])->save();

    $sync = app(SyncStripeSubscriptions::class);
    $sync->mirror($tenant, stripeSubscription('price_pro'));

    expect($tenant->fresh()->subscribed())->toBeTrue();

    $sync->mirror($tenant, stripeSubscription('price_pro', 'canceled'));

    expect($tenant->fresh()->subscribed())->toBeFalse();

    actingAs($user)->get('/')->assertRedirect(route('billing.index'));
});

test('a tenant without a stripe customer is a no-op', function () {
    [, $tenant] = syncFixture('sync-no-customer@example.com', stripeId: null);

    // Never reaches the Stripe API, so this must not throw.
    expect(app(SyncStripeSubscriptions::class)->handle($tenant))->toBeFalse();
    expect($tenant->subscriptions()->count())->toBe(0);
});

test('a failed sync on the checkout return still renders the billing page', function () {
    [$user, $tenant] = syncFixture('sync-failure@example.com');
    $tenant->forceFill(['trial_ends_at' => now()->subDay()])->save();

    // No Stripe credentials in tests: handle() throws, and the page must survive
    // it — the webhook is still coming and the user can retry.
    actingAs($user)->get(route('billing.index', ['checkout' => 'success']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Billing/Index'));
});
