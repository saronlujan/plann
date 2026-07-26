<?php

use App\Enums\PlanSlug;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
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

    $user = User::create([
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

it('enforces the plan seat limit', function () {
    [$tenant] = billingTenantUser('seats@example.com');

    // Basic: 1 seat, already taken by the owner.
    expect($tenant->maxUsers())->toBe(1);
    expect($tenant->canAddUser())->toBeFalse();

    $tenant->update(['plan_slug' => PlanSlug::Pro->value]);
    $tenant->refresh();

    // Pro: 5 seats.
    expect($tenant->maxUsers())->toBe(5);
    expect($tenant->canAddUser())->toBeTrue();

    User::factory()->count(4)->create(['tenant_id' => $tenant->id]);

    expect($tenant->fresh()->canAddUser())->toBeFalse();
});
