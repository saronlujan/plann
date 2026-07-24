<?php

use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('authenticated users may view preferences', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
        'locale' => 'pt',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'preferences@example.com',
        'password' => 'password',
    ]);

    actingAs($user)
        ->get('/preferences')
        ->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $page->component('Preferences/Index')
                ->where('locale', 'pt')
                ->has('localeOptions', 3);
        });
});

test('users may update language from preferences', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
        'locale' => 'pt',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'preferences-language@example.com',
        'password' => 'password',
    ]);

    actingAs($user)
        ->patch('/preferences/language', [
            'locale' => 'es',
        ])
        ->assertRedirect();

    expect($tenant->fresh()?->locale)->toBe('es');
});

test('users may update preferences language to english', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
        'locale' => 'pt',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'preferences-language-en@example.com',
        'password' => 'password',
    ]);

    actingAs($user)
        ->patch('/preferences/language', [
            'locale' => 'en',
        ])
        ->assertRedirect();

    expect($tenant->fresh()?->locale)->toBe('en');
});
