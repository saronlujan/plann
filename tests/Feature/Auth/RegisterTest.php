<?php

use App\Enums\PlanSlug;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmailVerificationPinNotification;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Support\Facades\Notification;

test('guests may view the register page', function () {
    $this->get('/register')->assertSuccessful();
});

test('users may register and create an initial tenant', function () {
    Notification::fake();
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();

    // A fresh signup lands on the verification step, not the dashboard.
    $this->post('/register', [
        'name' => 'Novo Usuario',
        'email' => 'novo@example.com',
        'phone' => '+55 11987654321',
        'country_code' => 'BR',
        'currency_code' => 'BRL',
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
    expect($tenant?->activeCurrencies()->where('code', 'BRL')->exists())->toBeTrue();
    expect($tenant?->currencies()->where('code', 'BRL')->exists())->toBeTrue();
    expect($currency)->not->toBeNull();

    // A fresh signup starts on the Basic plan with a 14-day card-free trial.
    expect($tenant?->plan_slug)->toBe(PlanSlug::Basic);
    expect($tenant?->onTrial())->toBeTrue();
    expect($tenant?->trial_ends_at?->isFuture())->toBeTrue();
    expect(User::query()->where('email', 'novo@example.com')->value('locale'))->toBe('pt');
});
