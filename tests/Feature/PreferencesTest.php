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
            ->has('soundOptions', 5)
            ->where('preferences.theme', 'light')
            ->where('preferences.color', 'zinc')
            ->where('preferences.sound_theme', 'blip'));
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

test('users may toggle the paid sound preference', function () {
    $user = makePreferencesUser('prefs-sound@example.com');

    expect($user->refresh()->sound_enabled)->toBeTrue();

    actingAs($user)
        ->patch('/preferences', ['sound_enabled' => false])
        ->assertRedirect();

    expect($user->refresh()->sound_enabled)->toBeFalse();
});

test('users may configure due-date notifications', function () {
    $user = makePreferencesUser('prefs-notify@example.com');

    expect($user->refresh()->notifications_enabled)->toBeFalse();

    actingAs($user)
        ->patch('/preferences', [
            'notifications_enabled' => true,
            'notify_days_before' => 5,
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->notifications_enabled)->toBeTrue();
    expect($user->notify_days_before)->toBe(5);
});

test('users may pick which paid sound to play', function () {
    $user = makePreferencesUser('prefs-sound-theme@example.com');

    actingAs($user)
        ->patch('/preferences', ['sound_theme' => 'coin'])
        ->assertRedirect();

    expect($user->refresh()->sound_theme)->toBe('coin');

    actingAs($user)
        ->patch('/preferences', ['sound_theme' => 'invalid'])
        ->assertSessionHasErrors('sound_theme');
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
