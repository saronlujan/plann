<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guests may view the login page', function () {
    $this->get('/login')->assertSuccessful();
});

test('users may authenticate using valid credentials', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'pessoa@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => 'pessoa@example.com',
        'password' => 'password',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
});

test('login attempts are throttled after repeated failures', function () {
    $tenant = Tenant::create(['name' => 'Tenant Principal']);

    User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'pessoa@example.com',
        'password' => Hash::make('password'),
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => 'pessoa@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    // The 6th attempt is rejected before Auth::attempt() runs, so even the
    // correct password is refused while the throttle is active.
    $this->post('/login', [
        'email' => 'pessoa@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a successful login clears the throttle counter', function () {
    $tenant = Tenant::create(['name' => 'Tenant Principal']);

    User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'pessoa@example.com',
        'password' => Hash::make('password'),
    ]);

    for ($attempt = 0; $attempt < 4; $attempt++) {
        $this->post('/login', [
            'email' => 'pessoa@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/login', [
        'email' => 'pessoa@example.com',
        'password' => 'password',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
});
