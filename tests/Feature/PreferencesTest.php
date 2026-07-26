<?php

use App\Enums\UserColor;
use App\Enums\UserTheme;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function makePreferencesUser(string $email, string $locale = 'pt'): User
{
    $tenant = Tenant::create(['name' => 'Tenant Principal']);

    return User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
        'locale' => $locale,
    ]);
}

test('authenticated users may view preferences', function () {
    $user = makePreferencesUser('preferences@example.com');

    actingAs($user)
        ->get('/preferences')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Preferences/Index')
            ->where('locale', 'pt')
            ->has('localeOptions', 3)
            ->has('themeOptions', 2)
            ->has('colorOptions', 10)
            ->where('preferences.theme', 'light')
            ->where('preferences.color', 'zinc'));
});

test('users may update appearance and language preferences', function () {
    $user = makePreferencesUser('prefs-update@example.com');

    actingAs($user)
        ->patch('/preferences', [
            'locale' => 'es',
            'theme' => 'dark',
            'color' => 'blue',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->locale)->toBe('es');
    expect($user->theme)->toBe(UserTheme::Dark);
    expect($user->color)->toBe(UserColor::Blue);
});

test('the header switcher may update only the locale', function () {
    $user = makePreferencesUser('prefs-locale-only@example.com');

    actingAs($user)
        ->patch('/preferences', ['locale' => 'en'])
        ->assertRedirect();

    $user->refresh();

    expect($user->locale)->toBe('en');
    // Untouched preferences keep their defaults.
    expect($user->theme)->toBe(UserTheme::Light);
    expect($user->color)->toBe(UserColor::Zinc);
});

test('preferences update rejects an unsupported color', function () {
    $user = makePreferencesUser('prefs-invalid@example.com');

    actingAs($user)
        ->patch('/preferences', [
            'locale' => 'pt',
            'theme' => 'light',
            'color' => 'brown',
        ])
        ->assertSessionHasErrors('color');
});
