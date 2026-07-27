<?php

use App\Models\Country;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;

use function Pest\Laravel\withHeaders;

beforeEach(function () {
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();
});

/**
 * The signup form starts on a guessed country. Alphabetical order would put
 * Argentina in front of every visitor, including Brazilians.
 */
function defaultCountryFor(string $acceptLanguage): ?string
{
    $resolved = null;

    withHeaders(['Accept-Language' => $acceptLanguage])
        ->get(route('register'))
        ->assertSuccessful()
        ->assertInertia(function ($page) use (&$resolved): void {
            $resolved = $page->toArray()['props']['defaultCountry'] ?? null;
        });

    return $resolved;
}

test('the region in the browser languages wins', function () {
    expect(defaultCountryFor('pt-BR,pt;q=0.9'))->toBe('BR');
    expect(defaultCountryFor('es-PY,es;q=0.9'))->toBe('PY');
    expect(defaultCountryFor('es-AR,es;q=0.9'))->toBe('AR');
});

test('a region we do not serve falls back to the language', function () {
    // Portugal is not a market, but Portuguese still points at Brazil.
    expect(defaultCountryFor('pt-PT,pt;q=0.9'))->toBe('BR');
    // Mexican Spanish is not served either; Spanish resolves to Argentina.
    expect(defaultCountryFor('es-MX,es;q=0.9'))->toBe('AR');
});

test('a language without a region still resolves', function () {
    expect(defaultCountryFor('pt'))->toBe('BR');
    expect(defaultCountryFor('es'))->toBe('AR');
});

test('an unrelated language falls back to the primary market', function () {
    expect(defaultCountryFor('fr-FR,fr;q=0.9'))->toBe('BR');
});

test('the guess is limited to countries actually served', function () {
    Country::query()->where('code', 'BR')->update(['is_active' => false]);

    // Brazil is off: a Brazilian visitor must land on something selectable.
    $resolved = defaultCountryFor('pt-BR,pt;q=0.9');

    expect($resolved)->not->toBe('BR');
    expect(Country::query()->where('code', $resolved)->where('is_active', true)->exists())->toBeTrue();
});

test('the form starts on the guessed country and its currency', function () {
    withHeaders(['Accept-Language' => 'es-PY,es;q=0.9'])
        ->get(route('register'))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $props = $page->toArray()['props'];

            expect($props['defaultCountry'])->toBe('PY');

            $paraguay = collect($props['countryOptions'])->firstWhere('value', 'PY');

            // The form reads the currency off the option, so the pairing matters.
            expect($paraguay['currency'])->toBe('PYG');
        });
});
