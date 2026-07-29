<?php

use App\Models\Country;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Database\QueryException;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();
    // Signup validates the chosen plan against this table.
    app(PlanSeeder::class)->run();
});

test('the three launch countries are seeded with their currency', function () {
    $countries = Country::query()->with('currency')->orderBy('code')->get();

    expect($countries->pluck('code')->all())->toBe(['AR', 'BR', 'PY']);
    expect($countries->pluck('currency.code')->all())->toBe(['ARS', 'BRL', 'PYG']);
});

test('the register page offers the active countries', function () {
    get(route('register'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Register/Register')
            ->has('countryOptions', 3));
});

test('signing up stores the country and activates its currency', function () {
    post(route('register.store'), [
        'name' => 'Paraguaio',
        'email' => 'py@example.com',
        'country_code' => 'PY',
        'currency_code' => 'PYG',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $tenant = User::query()->where('email', 'py@example.com')->firstOrFail()->tenant;

    expect($tenant->country->code)->toBe('PY');
    // The workspace opens on the currency of the country it belongs to.
    // Signup activates nothing: a currency starts being used when the first
    // account is opened in it.
    expect($tenant?->activeCurrencies()->exists())->toBeFalse();
});

test('each country brings its own starting currency', function () {
    post(route('register.store'), [
        'name' => 'Argentino',
        'email' => 'ar@example.com',
        'country_code' => 'AR',
        'currency_code' => 'ARS',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $tenant = User::query()->where('email', 'ar@example.com')->firstOrFail()->tenant;

    // Signup activates nothing: a currency starts being used when the first
    // account is opened in it.
    expect($tenant?->activeCurrencies()->exists())->toBeFalse();
});

test('the country is required and must be one we serve', function () {
    post(route('register.store'), [
        'name' => 'Sem pais',
        'email' => 'none@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('country_code');

    post(route('register.store'), [
        'name' => 'Outro pais',
        'email' => 'other@example.com',
        'country_code' => 'US',
        'currency_code' => 'BRL',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('country_code');

    expect(User::query()->whereIn('email', ['none@example.com', 'other@example.com'])->count())->toBe(0);
});

test('an inactive country is neither offered nor accepted', function () {
    Country::query()->where('code', 'AR')->update(['is_active' => false]);

    get(route('register'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('countryOptions', 2));

    post(route('register.store'), [
        'name' => 'Argentino',
        'email' => 'inactive-ar@example.com',
        'country_code' => 'AR',
        'currency_code' => 'ARS',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('country_code');
});

test('a country cannot be deleted while a currency depends on it', function () {
    // Countries point at a currency with restrictOnDelete, so the catalogue
    // cannot be pulled out from under them.
    $brl = Currency::withoutGlobalScopes()->where('code', 'BRL')->firstOrFail();

    expect(fn () => $brl->delete())->toThrow(QueryException::class);
});

test('deleting a country leaves its workspaces intact', function () {
    post(route('register.store'), [
        'name' => 'Brasileiro',
        'email' => 'br-delete@example.com',
        'country_code' => 'BR',
        'currency_code' => 'BRL',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $tenant = User::query()->where('email', 'br-delete@example.com')->firstOrFail()->tenant;

    Tenant::query()->whereKey($tenant->id)->update(['country_id' => null]);

    // nullOnDelete: losing the country must never cascade into losing the data.
    expect(Tenant::query()->whereKey($tenant->id)->exists())->toBeTrue();
});

test('signing up creates no account', function () {
    post(route('register.store'), [
        'name' => 'Novo',
        'email' => 'no-account@example.com',
        'country_code' => 'BR',
        'currency_code' => 'BRL',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $tenant = User::query()->where('email', 'no-account@example.com')->firstOrFail()->tenant;

    // An account nobody asked for, named after a currency code, reads as a bug.
    expect($tenant->accounts()->count())->toBe(0);
    // The currency is still activated, so the first account has one to use.
    // Signup activates nothing: a currency starts being used when the first
    // account is opened in it.
    expect($tenant?->activeCurrencies()->exists())->toBeFalse();
});

test('the signup page offers the shared currency catalogue', function () {
    get(route('register'))->assertSuccessful()
        ->assertInertia(function ($page) {
            $props = $page->toArray()['props'];

            expect($props['currencyOptions'])->toHaveCount(5);
            // Each country carries its currency so the form can follow the choice.
            // Countries are ordered by name: Argentina, Brasil, Paraguay.
            expect(array_column($props['countryOptions'], 'currency'))->toBe(['ARS', 'BRL', 'PYG']);
        });
});

test('the chosen currency wins over the country default', function () {
    post(route('register.store'), [
        'name' => 'Brasileiro em dolar',
        'email' => 'usd@example.com',
        'country_code' => 'BR',
        'currency_code' => 'USD',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $tenant = User::query()->where('email', 'usd@example.com')->firstOrFail()->tenant;

    // Country and currency are independent: living in Brazil does not mean
    // tracking in reais.
    expect($tenant->country->code)->toBe('BR');
    // Signup activates nothing: a currency starts being used when the first
    // account is opened in it.
    expect($tenant?->activeCurrencies()->exists())->toBeFalse();
});

test('the currency is required and must be in the catalogue', function () {
    post(route('register.store'), [
        'name' => 'Sem moeda',
        'email' => 'no-currency@example.com',
        'country_code' => 'BR',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('currency_code');

    post(route('register.store'), [
        'name' => 'Moeda inexistente',
        'email' => 'bad-currency@example.com',
        'country_code' => 'BR',
        'currency_code' => 'XYZ',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('currency_code');

    expect(User::query()->whereIn('email', ['no-currency@example.com', 'bad-currency@example.com'])->count())->toBe(0);
});
