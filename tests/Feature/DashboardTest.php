<?php

use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('authenticated users may view the dashboard', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'dashboard@example.com',
        'password' => 'password',
    ]);

    actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $page->component('Dashboard/Index')
                ->where('auth.user.name', 'Pessoa Teste');
        });
});
