<?php

use App\Enums\UserColor;
use App\Enums\UserTheme;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function makePreferencesUser(string $email, string $locale = 'pt'): User
{
    $tenant = Tenant::create(['name' => 'Tenant Principal']);

    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
        'locale' => $locale,
    ]);
}

/**
 * @return array{0: User, 1: Tenant}
 */
function preferencesFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);

    return [$user, $tenant];
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
    expect($user->color)->toBe(UserColor::Blue->value);
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
    expect($user->color)->toBe(UserColor::Zinc->value);
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

test('a user may set a default currency from the ones in use', function () {
    [$user, $tenant] = preferencesFixture('default-currency@example.com');

    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);

    app(TenantContext::class)->setTenantId($tenant->id);
    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $usd->id, 'name' => 'Conta USD']);

    actingAs($user)
        ->patch(route('preferences.update'), ['default_currency_id' => $usd->id])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->refresh()->default_currency_id)->toBe($usd->id);
});

test('a currency the workspace holds no account in is rejected', function () {
    [$user, $tenant] = preferencesFixture('inactive-currency@example.com');

    $brl = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);

    actingAs($user)
        ->patch(route('preferences.update'), ['default_currency_id' => $usd->id])
        ->assertSessionHasErrors('default_currency_id');

    expect($user->refresh()->default_currency_id)->toBeNull();
});

test('another tenant currency activation does not authorize the choice', function () {
    [$user] = preferencesFixture('cross-tenant-currency@example.com');

    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);

    // A *different* workspace activates USD; this user still may not pick it.
    [, $otherTenant] = preferencesFixture('other-workspace@example.com');

    actingAs($user)
        ->patch(route('preferences.update'), ['default_currency_id' => $usd->id])
        ->assertSessionHasErrors('default_currency_id');

    expect($user->refresh()->default_currency_id)->toBeNull();
});

test('the default currency may be cleared back to no preference', function () {
    [$user, $tenant] = preferencesFixture('clear-currency@example.com');

    $brl = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $user->update(['default_currency_id' => $brl->id]);

    actingAs($user)
        ->patch(route('preferences.update'), ['default_currency_id' => null])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->default_currency_id)->toBeNull();
});

test('the preferences page exposes the active currencies and the current choice', function () {
    [$user, $tenant] = preferencesFixture('currency-options@example.com');

    $brl = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);
    $user->update(['default_currency_id' => $usd->id]);

    app(TenantContext::class)->setTenantId($tenant->id);
    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $brl->id, 'name' => 'Conta BRL']);
    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $usd->id, 'name' => 'Conta USD']);

    actingAs($user)->get(route('preferences'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('preferences.default_currency_id', $usd->id)
            ->has('currencyOptions', 2));
});

test('a currency with no account is not offered as a default', function () {
    [$user, $tenant] = preferencesFixture('currency-without-account@example.com');

    $brl = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);

    app(TenantContext::class)->setTenantId($tenant->id);
    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $brl->id, 'name' => 'Conta BRL']);

    // USD is activated but has nowhere to hold money, so it is not a real choice.
    actingAs($user)->get(route('preferences'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('currencyOptions', 1)
            ->where('currencyOptions.0.value', (string) $brl->id));
});

test('the accent may be a colour of the user own choosing', function () {
    $user = makePreferencesUser('accent-custom@example.com');

    actingAs($user)
        ->patch('/preferences', ['color' => '#6361F3'])
        ->assertRedirect();

    // Folded to lowercase, so the same accent is never stored two ways.
    expect($user->fresh()?->color)->toBe('#6361f3');
});

test('an accent that is neither in the palette nor a hex is rejected', function () {
    $user = makePreferencesUser('accent-invalid@example.com');

    foreach (['rebeccapurple', '#63f', '#6361FG', 'blue; background: red'] as $color) {
        actingAs($user)
            ->patch('/preferences', ['color' => $color])
            ->assertSessionHasErrors('color');
    }

    // Nothing got through: the accent is still the default.
    expect($user->fresh()?->color)->toBe('zinc');
});
