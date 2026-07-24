<?php

use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('authenticated users may view settings', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
        'locale' => 'pt',
    ]);

    Currency::query()->updateOrCreate([
        'code' => 'BRL',
    ], [
        'name' => 'Brazilian Real',
        'symbol' => 'R$',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'settings@example.com',
        'password' => 'password',
    ]);

    actingAs($user)
        ->get('/settings')
        ->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $page->component('Settings/Index')
                ->where('locale', 'pt')
                ->has('currencies');
        });
});

test('users may update active currencies independently', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
        'locale' => 'pt',
    ]);

    $brl = Currency::query()->updateOrCreate([
        'code' => 'BRL',
    ], [
        'name' => 'Brazilian Real',
        'symbol' => 'R$',
    ]);

    $usd = Currency::query()->updateOrCreate([
        'code' => 'USD',
    ], [
        'name' => 'United States Dollar',
        'symbol' => '$',
    ]);

    $tenant->syncCurrencyActivations([$brl->id]);
    $tenant->ensureCurrencyAssets([$brl->id]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'settings-currencies@example.com',
        'password' => 'password',
    ]);

    actingAs($user)
        ->patch('/settings/currencies', [
            'currency_ids' => [$brl->id, $usd->id],
        ])
        ->assertRedirect();

    expect($tenant->fresh()?->activeCurrencies()->pluck('code')->all())->toBe(['BRL', 'USD']);
    expect($tenant->accounts()->where('currency_id', $usd->id)->exists())->toBeTrue();
});

test('users may update the tenant language independently', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
        'locale' => 'pt',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'settings-language@example.com',
        'password' => 'password',
    ]);

    actingAs($user)
        ->patch('/settings/language', [
            'locale' => 'es',
        ])
        ->assertRedirect();

    expect($tenant->fresh()?->locale)->toBe('es');
});
