<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function profileUser(string $email): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    return User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);
}

test('users may view their profile', function () {
    $user = profileUser('profile@example.com');

    actingAs($user)->get('/profile')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/Index')
            ->where('profile.email', 'profile@example.com'));
});

test('users may update their account details', function () {
    $user = profileUser('profile-update@example.com');

    actingAs($user)
        ->patch('/profile', [
            'name' => 'Novo Nome',
            'email' => 'novo@example.com',
            'phone' => '+5511999999999',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->name)->toBe('Novo Nome');
    expect($user->email)->toBe('novo@example.com');
    expect($user->phone)->toBe('+5511999999999');
});

test('users may change their password with the correct current password', function () {
    $user = profileUser('profile-pass@example.com');

    actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
        ->assertRedirect();

    expect(Hash::check('new-strong-password', $user->refresh()->password))->toBeTrue();
});

test('the current password must be correct', function () {
    $user = profileUser('profile-wrong@example.com');

    actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('the new password must be confirmed', function () {
    $user = profileUser('profile-confirm@example.com');

    actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'mismatch',
        ])
        ->assertSessionHasErrors('password');
});
