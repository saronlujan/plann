<?php

use App\Enums\PlanSlug;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Tenant}
 */
function currencyOwner(string $email, PlanSlug $plan = PlanSlug::Pro): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email, 'plan_slug' => $plan->value]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => $email]);

    return [$user, $tenant];
}

function globalCurrency(string $code, string $name = 'Global', string $symbol = '$'): Currency
{
    return Currency::withoutGlobalScopes()->firstOrCreate(
        ['code' => $code, 'tenant_id' => null],
        ['name' => $name, 'symbol' => $symbol],
    );
}

test('a workspace may add a currency the catalogue does not carry', function () {
    [$user, $tenant] = currencyOwner('add-currency@example.com');

    actingAs($user)
        ->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    expect($currency->tenant_id)->toBe($tenant->id);
    expect($currency->isGlobal())->toBeFalse();
});

test('the code is normalised to uppercase', function () {
    [$user] = currencyOwner('upper-currency@example.com');

    actingAs($user)->post(route('currencies.store'), [
        'name' => 'Euro',
        'code' => 'eur',
        'symbol' => '€',
    ])->assertSessionHasNoErrors();

    expect(Currency::query()->where('code', 'EUR')->exists())->toBeTrue();
});

test('a custom currency cannot shadow one from the shared catalogue', function () {
    globalCurrency('BRL', 'Real', 'R$');

    [$user] = currencyOwner('shadow-currency@example.com');

    actingAs($user)
        ->post(route('currencies.store'), ['name' => 'Meu Real', 'code' => 'BRL', 'symbol' => 'R$'])
        ->assertSessionHasErrors('code');
});

test('the same code cannot be added twice by one workspace', function () {
    [$user] = currencyOwner('duplicate-currency@example.com');

    $payload = ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€'];

    actingAs($user)->post(route('currencies.store'), $payload)->assertSessionHasNoErrors();
    actingAs($user)->post(route('currencies.store'), $payload)->assertSessionHasErrors('code');
});

test('two workspaces may each register the same code independently', function () {
    [$first] = currencyOwner('first-eur@example.com');
    actingAs($first)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);

    [$second] = currencyOwner('second-eur@example.com');

    // Custom currencies are private, so they cannot collide across workspaces.
    actingAs($second)
        ->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€'])
        ->assertSessionHasNoErrors();

    expect(Currency::withoutGlobalScopes()->where('code', 'EUR')->count())->toBe(2);
});

test('a workspace never sees another workspace custom currency', function () {
    globalCurrency('BRL', 'Real', 'R$');

    [$owner, $ownerTenant] = currencyOwner('owner-currency@example.com');
    actingAs($owner)->post(route('currencies.store'), ['name' => 'Bitcoin', 'code' => 'BTC', 'symbol' => '₿']);

    [$stranger] = currencyOwner('stranger-currency@example.com');

    actingAs($stranger)->get(route('currencies.index'))->assertSuccessful()
        ->assertInertia(function ($page) {
            $codes = array_column($page->toArray()['props']['currencies'], 'code');

            expect($codes)->toContain('BRL');   // the shared catalogue
            expect($codes)->not->toContain('BTC'); // someone else's private one
        });

    expect($ownerTenant->id)->not->toBeNull();
});

test('a custom currency may be removed while unused', function () {
    [$user] = currencyOwner('remove-currency@example.com');

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    actingAs($user)->delete(route('currencies.destroy', $currency))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Currency::withoutGlobalScopes()->whereKey($currency->id)->exists())->toBeFalse();
});

test('a currency with entries is not removed', function () {
    [$user, $tenant] = currencyOwner('used-currency@example.com');

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta EUR',
        'balance' => 0,
    ]);

    Transaction::create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-01',
        'amount' => 100,
        'adjustment_amount' => 0,
        'description' => 'Movimento',
    ]);

    actingAs($user)->delete(route('currencies.destroy', $currency))
        ->assertSessionHasErrors('currency');

    // Neither the currency nor the entry that depends on it may disappear.
    expect(Currency::withoutGlobalScopes()->whereKey($currency->id)->exists())->toBeTrue();
    expect(Transaction::query()->count())->toBe(1);
    expect(Account::query()->whereKey($account->id)->exists())->toBeTrue();
});

test('an empty account does not block removing its currency', function () {
    [$user, $tenant] = currencyOwner('empty-account@example.com');

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta EUR',
        'balance' => 0,
    ]);

    actingAs($user)->delete(route('currencies.destroy', $currency))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // accounts.currency_id is RESTRICT, so the empty account goes with it.
    expect(Currency::withoutGlobalScopes()->whereKey($currency->id)->exists())->toBeFalse();
    expect(Account::query()->whereKey($account->id)->exists())->toBeFalse();
});

test('removing a currency leaves other currencies accounts alone', function () {
    [$user, $tenant] = currencyOwner('other-accounts@example.com');

    $brl = globalCurrency('BRL', 'Real', 'R$');
    $keep = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $brl->id,
        'name' => 'Conta BRL',
        'balance' => 0,
    ]);

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta EUR',
        'balance' => 0,
    ]);

    actingAs($user)->delete(route('currencies.destroy', $currency))->assertSessionHasNoErrors();

    expect(Account::query()->whereKey($keep->id)->exists())->toBeTrue();
    expect(Account::query()->count())->toBe(1);
});

test('the shared catalogue cannot be deleted by a workspace', function () {
    $brl = globalCurrency('BRL', 'Real', 'R$');

    [$user] = currencyOwner('delete-global@example.com');

    actingAs($user)->delete(route('currencies.destroy', $brl))->assertForbidden();

    expect(Currency::withoutGlobalScopes()->whereKey($brl->id)->exists())->toBeTrue();
});

test('one workspace cannot delete another workspace currency', function () {
    [$owner] = currencyOwner('victim-currency@example.com');
    actingAs($owner)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::withoutGlobalScopes()->where('code', 'EUR')->firstOrFail();

    [$attacker] = currencyOwner('attacker-currency@example.com');

    actingAs($attacker)->delete(route('currencies.destroy', $currency))->assertNotFound();

    expect(Currency::withoutGlobalScopes()->whereKey($currency->id)->exists())->toBeTrue();
});

test('a workspace may edit its own currency', function () {
    [$user] = currencyOwner('edit-currency@example.com');

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    actingAs($user)
        ->patch(route('currencies.update', $currency), [
            'name' => 'Euro Europeu',
            'code' => 'EU',
            'symbol' => 'E',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $currency->refresh();

    expect($currency->name)->toBe('Euro Europeu');
    expect($currency->code)->toBe('EU');
    expect($currency->symbol)->toBe('E');
});

test('editing may keep the same code', function () {
    [$user] = currencyOwner('same-code-edit@example.com');

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    // The unique rule must ignore the row being edited.
    actingAs($user)
        ->patch(route('currencies.update', $currency), [
            'name' => 'Euro renomeado',
            'code' => 'EUR',
            'symbol' => '€',
        ])
        ->assertSessionHasNoErrors();

    expect($currency->fresh()->name)->toBe('Euro renomeado');
});

test('editing cannot take a code already in the catalogue', function () {
    globalCurrency('BRL', 'Real', 'R$');

    [$user] = currencyOwner('conflict-edit@example.com');
    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    actingAs($user)
        ->patch(route('currencies.update', $currency), [
            'name' => 'Meu Real',
            'code' => 'BRL',
            'symbol' => 'R$',
        ])
        ->assertSessionHasErrors('code');

    expect($currency->fresh()->code)->toBe('EUR');
});

test('the shared catalogue cannot be edited by a workspace', function () {
    $brl = globalCurrency('BRL', 'Real', 'R$');

    [$user] = currencyOwner('edit-global@example.com');

    actingAs($user)
        ->patch(route('currencies.update', $brl), ['name' => 'Hackeado', 'code' => 'BRL', 'symbol' => 'X'])
        ->assertForbidden();

    expect($brl->fresh()->name)->toBe('Real');
});

test('one workspace cannot edit another workspace currency', function () {
    [$owner] = currencyOwner('victim-edit@example.com');
    actingAs($owner)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::withoutGlobalScopes()->where('code', 'EUR')->firstOrFail();

    [$attacker] = currencyOwner('attacker-edit@example.com');

    actingAs($attacker)
        ->patch(route('currencies.update', $currency), ['name' => 'Roubado', 'code' => 'EUR', 'symbol' => '€'])
        ->assertNotFound();

    expect($currency->fresh()->name)->toBe('Euro');
});

test('the page flags which currencies the workspace owns', function () {
    globalCurrency('BRL', 'Real', 'R$');

    [$user] = currencyOwner('flag-currency@example.com');
    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);

    // This flag is what shows the edit and delete buttons. Without tenant_id in
    // the select every row looked global and both buttons disappeared.
    actingAs($user)->get(route('currencies.index'))->assertSuccessful()
        ->assertInertia(function ($page) {
            $rows = collect($page->toArray()['props']['currencies'])->keyBy('code');

            expect($rows['EUR']['custom'])->toBeTrue();
            expect($rows['BRL']['custom'])->toBeFalse();
        });
});

test('an activated but unused currency can still be removed', function () {
    [$user, $tenant] = currencyOwner('active-removable@example.com');

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    $tenant->syncCurrencyActivations([$currency->id]);

    // Being active is not the same as being used: only accounts and entries block
    // the delete.
    actingAs($user)->delete(route('currencies.destroy', $currency))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Currency::withoutGlobalScopes()->whereKey($currency->id)->exists())->toBeFalse();
});

test('a single-currency plan cannot add a currency', function () {
    [$basic] = currencyOwner('basic-add@example.com', PlanSlug::Basic);

    // Creating a currency it could never activate is the incoherence this guard
    // exists to prevent.
    actingAs($basic)
        ->post(route('currencies.store'), ['name' => 'Libra', 'code' => 'GBP', 'symbol' => '£'])
        ->assertForbidden();

    expect(Currency::withoutGlobalScopes()->where('code', 'GBP')->exists())->toBeFalse();
});

test('a downgraded workspace keeps its currency but can no longer manage it', function () {
    [$user, $tenant] = currencyOwner('downgrade-manage@example.com');

    actingAs($user)->post(route('currencies.store'), ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
    $currency = Currency::query()->where('code', 'EUR')->firstOrFail();

    $tenant->update(['plan_slug' => PlanSlug::Basic->value]);

    actingAs($user)
        ->patch(route('currencies.update', $currency), ['name' => 'X', 'code' => 'EUR', 'symbol' => '€'])
        ->assertForbidden();

    actingAs($user)->delete(route('currencies.destroy', $currency))->assertForbidden();

    // Managing is gated, but nothing the workspace already had is taken away.
    expect(Currency::withoutGlobalScopes()->whereKey($currency->id)->exists())->toBeTrue();
    expect($currency->fresh()->name)->toBe('Euro');
});
