<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

function trialUser(string $email): User
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

test('the dev button forces the trial to expire', function () {
    $user = trialUser('expire@example.com');

    expect($user->tenant()->first()->onTrial())->toBeTrue();

    actingAs($user)
        ->post('/dev/expire-trial')
        ->assertRedirect(route('billing.index'));

    $tenant = $user->tenant()->first();
    expect($tenant->onTrial())->toBeFalse();
    expect($tenant->subscribed())->toBeFalse();
});

test('expiring the trial locks the app behind the paywall', function () {
    $user = trialUser('paywall@example.com');

    actingAs($user)->post('/dev/expire-trial');

    // Subscribed middleware now redirects app pages to billing.
    actingAs($user)->get('/')->assertRedirect(route('billing.index'));
});
