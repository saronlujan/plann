<?php

use App\Enums\PlanSlug;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Tenant, 2: Currency, 3: Currency}
 */
function multiCurrencyFixture(string $email, PlanSlug $plan = PlanSlug::Basic): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email, 'plan_slug' => $plan->value]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => $email,
        'locale' => 'pt',
    ]);

    $brl = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);

    return [$user, $tenant, $brl, $usd];
}

test('basic may activate a single currency', function () {
    [$user, $tenant, $brl] = multiCurrencyFixture('one-currency@example.com');

    actingAs($user)
        ->patch(route('currencies.activations'), ['currency_ids' => [$brl->id]])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tenant->activeCurrencies()->count())->toBe(1);
});

test('basic is refused a second currency', function () {
    [$user, $tenant, $brl, $usd] = multiCurrencyFixture('two-currencies@example.com');

    actingAs($user)
        ->patch(route('currencies.activations'), ['currency_ids' => [$brl->id, $usd->id]])
        ->assertSessionHasErrors('currency_ids');

    expect($tenant->activeCurrencies()->count())->toBe(0);
});

test('pro may activate several currencies', function () {
    [$user, $tenant, $brl, $usd] = multiCurrencyFixture('pro-currencies@example.com', PlanSlug::Pro);

    actingAs($user)
        ->patch(route('currencies.activations'), ['currency_ids' => [$brl->id, $usd->id]])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tenant->activeCurrencies()->count())->toBe(2);
});

test('a downgrade never strips currencies the workspace already uses', function () {
    [$user, $tenant, $brl, $usd] = multiCurrencyFixture('downgrade@example.com', PlanSlug::Pro);

    $tenant->syncCurrencyActivations([$brl->id, $usd->id]);

    // Back to Basic: the two currencies stay, because accounts and transactions
    // still reference them. Hiding that data would be worse than the revenue leak.
    $tenant->update(['plan_slug' => PlanSlug::Basic->value]);

    actingAs($user)
        ->patch(route('currencies.activations'), ['currency_ids' => [$brl->id, $usd->id]])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($tenant->fresh()->activeCurrencies()->count())->toBe(2);
});

test('a downgraded workspace still cannot grow past what it had', function () {
    [$user, $tenant, $brl, $usd] = multiCurrencyFixture('no-growth@example.com', PlanSlug::Pro);

    $tenant->syncCurrencyActivations([$brl->id, $usd->id]);
    $tenant->update(['plan_slug' => PlanSlug::Basic->value]);

    $ars = Currency::query()->firstOrCreate(['code' => 'ARS'], ['name' => 'Peso', 'symbol' => '$']);

    actingAs($user)
        ->patch(route('currencies.activations'), ['currency_ids' => [$brl->id, $usd->id, $ars->id]])
        ->assertSessionHasErrors('currency_ids');

    expect($tenant->fresh()->activeCurrencies()->count())->toBe(2);
});

test('the currencies page tells the UI whether the plan unlocks the feature', function () {
    [$basicUser] = multiCurrencyFixture('basic-flag@example.com');

    actingAs($basicUser)->get(route('currencies.index'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('canUseMultiCurrency', false));

    [$proUser] = multiCurrencyFixture('pro-flag@example.com', PlanSlug::Pro);

    actingAs($proUser)->get(route('currencies.index'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('canUseMultiCurrency', true));
});

test('the refusal carries a message the UI can show', function () {
    [$user, $tenant, $brl, $usd] = multiCurrencyFixture('feedback@example.com');

    $response = actingAs($user)
        ->patch(route('currencies.activations'), ['currency_ids' => [$brl->id, $usd->id]])
        ->assertSessionHasErrors('currency_ids');

    // The toggle has no other way to explain itself: without a message the switch
    // just snaps back and nothing tells the user why.
    $message = session('errors')->first('currency_ids');

    expect($message)->toBe(__('currencies.errors.plan_limit'));
    expect($tenant->activeCurrencies()->count())->toBe(0);
    expect($response)->not->toBeNull();
});

test('a workspace grandfathered above the limit may still swap within it', function () {
    [$user, $tenant, $brl, $usd] = multiCurrencyFixture('swap@example.com', PlanSlug::Pro);

    $tenant->syncCurrencyActivations([$brl->id, $usd->id]);
    $tenant->update(['plan_slug' => PlanSlug::Basic->value]);

    // Two active is the ceiling now; trading one for another stays within it.
    $ars = Currency::query()->firstOrCreate(['code' => 'ARS'], ['name' => 'Peso', 'symbol' => '$']);

    actingAs($user)
        ->patch(route('currencies.activations'), ['currency_ids' => [$brl->id, $ars->id]])
        ->assertSessionHasNoErrors();

    expect($tenant->fresh()->activeCurrencies()->pluck('code')->sort()->values()->all())
        ->toBe(['ARS', 'BRL']);
});
