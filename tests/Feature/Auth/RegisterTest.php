<?php

use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;

test('guests may view the register page', function () {
    $this->get('/register')->assertSuccessful();
});

test('users may register and create an initial tenant', function () {
    $this->post('/register', [
        'name' => 'Novo Usuario',
        'email' => 'novo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/');

    $tenant = Tenant::query()->where('name', 'Novo Usuario')->first();
    $currency = Currency::query()->where('code', 'BRL')->first();

    expect($tenant)->not->toBeNull();
    expect(User::query()->where('email', 'novo@example.com')->exists())->toBeTrue();
    expect($tenant?->activeCurrencies()->where('code', 'BRL')->exists())->toBeTrue();
    expect($tenant?->currencies()->where('code', 'BRL')->exists())->toBeTrue();
    expect($currency)->not->toBeNull();
});
