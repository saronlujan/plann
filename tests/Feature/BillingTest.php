<?php

use App\Enums\PlanFeature;
use App\Enums\PlanSlug;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: Tenant, 1: User}
 */
function billingTenantUser(string $email, ?callable $tenantSetup = null): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);

    if ($tenantSetup !== null) {
        $tenantSetup($tenant);
    }

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);

    return [$tenant->fresh(), $user];
}

it('lets a tenant on trial use the app', function () {
    [, $user] = billingTenantUser('trial@example.com');

    actingAs($user)->get('/transactions')->assertSuccessful();
});

it('redirects an expired tenant without a subscription to billing', function () {
    [, $user] = billingTenantUser('expired@example.com', fn (Tenant $t) => $t->forceFill([
        'trial_ends_at' => now()->subDay(),
    ])->save());

    actingAs($user)->get('/transactions')->assertRedirect(route('billing.index'));
});

it('keeps the app accessible for an active subscription after the trial', function () {
    [$tenant, $user] = billingTenantUser('subscribed@example.com', function (Tenant $t): void {
        $t->forceFill(['trial_ends_at' => now()->subDay(), 'stripe_id' => 'cus_test'])->save();
        $t->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
        ]);
    });

    expect($tenant->subscribed())->toBeTrue();

    actingAs($user)->get('/transactions')->assertSuccessful();
});

it('keeps billing reachable even when the trial has lapsed', function () {
    Plan::factory()->create();
    Plan::factory()->pro()->create();

    [, $user] = billingTenantUser('lapsed@example.com', fn (Tenant $t) => $t->forceFill([
        'trial_ends_at' => now()->subDay(),
    ])->save());

    actingAs($user)
        ->get('/billing')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Billing/Index')
            ->has('plans', 2)
            ->where('status.on_trial', false)
            ->where('status.subscribed', false));
});

it('separates the plans by capability, not by seats', function () {
    [$tenant] = billingTenantUser('features@example.com');

    expect($tenant->hasFeature(PlanFeature::MultiCurrency))->toBeFalse();

    $tenant->update(['plan_slug' => PlanSlug::Pro->value]);

    expect($tenant->fresh()->hasFeature(PlanFeature::MultiCurrency))->toBeTrue();
});

it('allows a single user per workspace', function () {
    [$tenant] = billingTenantUser('single-user@example.com');

    // The app is personal: the schema itself refuses a second user.
    expect(fn () => User::factory()->create(['tenant_id' => $tenant->id]))
        ->toThrow(QueryException::class);
});
