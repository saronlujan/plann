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

    User::create([
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
