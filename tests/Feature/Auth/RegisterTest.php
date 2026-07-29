<?php

use App\Enums\PlanFeature;
use App\Enums\PlanSlug;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmailVerificationPinNotification;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Notification;

test('guests may view the register page', function () {
    $this->get('/register')->assertSuccessful();
});

test('users may register and create an initial tenant', function () {
    Notification::fake();
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();
    app(PlanSeeder::class)->run();

    // A fresh signup lands on the verification step, not the dashboard.
    $this->post('/register', [
        'name' => 'Novo Usuario',
        'email' => 'novo@example.com',
        'phone' => '+55 11987654321',
        'country_code' => 'BR',
        'currency_code' => 'BRL',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'novo@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, EmailVerificationPinNotification::class);

    expect(User::query()->where('email', 'novo@example.com')->value('phone'))->toBe('+55 11987654321');

    $tenant = Tenant::query()->where('name', 'Novo Usuario')->first();
    $currency = Currency::query()->where('code', 'BRL')->first();

    expect($tenant)->not->toBeNull();
    expect(User::query()->where('email', 'novo@example.com')->exists())->toBeTrue();
    // Signup activates nothing: a currency starts being used when the first
    // account is opened in it.
    expect($tenant?->activeCurrencies()->exists())->toBeFalse();
    expect($currency)->not->toBeNull();

    // A fresh signup starts on the Basic plan with a 14-day card-free trial.
    expect($tenant?->plan_slug)->toBe(PlanSlug::Basic);
    expect($tenant?->onTrial())->toBeTrue();
    expect($tenant?->trial_ends_at?->isFuture())->toBeTrue();
    expect(User::query()->where('email', 'novo@example.com')->value('locale'))->toBe('pt');
});

test('the register page offers the active plans with price and description', function () {
    app(PlanSeeder::class)->run();
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();

    // Name and description come from the enum, so they follow the visitor's
    // language rather than whatever locale seeded the plans table.
    $this->withHeaders(['Accept-Language' => 'pt-BR'])
        ->get(route('register'))
        ->assertSuccessful()
        ->assertInertia(function ($page): void {
            $plans = collect($page->toArray()['props']['planOptions'])->keyBy('value');

            expect($plans)->toHaveCount(2);
            expect($plans['basic']['monthly_price_cents'])->toBe(990);
            expect($plans['pro']['monthly_price_cents'])->toBe(1990);
            // The card advertises a monthly figure but the charge is yearly, so
            // both numbers have to reach the page.
            expect($plans['basic']['annual_price_cents'])->toBe(990 * 12);
            expect($plans['pro']['annual_price_cents'])->toBe(1990 * 12);
            expect($plans['pro']['description'])->toContain('empreendedores');
        });
});

test('the chosen plan is the one the workspace starts its trial on', function () {
    Notification::fake();
    app(PlanSeeder::class)->run();
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();

    $this->post('/register', [
        'name' => 'Autônomo',
        'email' => 'autonomo@example.com',
        'country_code' => 'BR',
        'currency_code' => 'BRL',
        'plan_slug' => 'pro',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $tenant = Tenant::query()->where('name', 'Autônomo')->firstOrFail();

    // Trialling the tier they picked is the whole point: on Basic they would
    // never see multi-currency before deciding whether to pay for it.
    expect($tenant->plan_slug)->toBe(PlanSlug::Pro);
    expect($tenant->onTrial())->toBeTrue();
    expect($tenant->hasFeature(PlanFeature::MultiCurrency))->toBeTrue();
});

test('registration refuses a plan that is not on offer', function () {
    app(PlanSeeder::class)->run();
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();

    $this->post('/register', [
        'name' => 'Novo Usuario',
        'email' => 'plano-invalido@example.com',
        'country_code' => 'BR',
        'currency_code' => 'BRL',
        'plan_slug' => 'enterprise',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('plan_slug');

    expect(User::query()->where('email', 'plano-invalido@example.com')->exists())->toBeFalse();
});
