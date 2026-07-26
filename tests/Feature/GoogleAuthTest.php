<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

test('it redirects to google for authentication', function (): void {
    Config::set('services.google.client_id', 'google-client-id');
    Config::set('services.google.client_secret', 'google-client-secret');
    Config::set('services.google.redirect', 'http://plann.test/auth/google/callback');

    Socialite::shouldReceive('driver->redirect')
        ->once()
        ->andReturn(redirect()->away('https://accounts.google.test/auth'));

    get(route('auth.google.redirect'))
        ->assertRedirect('https://accounts.google.test/auth');
});

test('it creates a user and tenant from the google callback', function (): void {
    Config::set('services.google.client_id', 'google-client-id');
    Config::set('services.google.client_secret', 'google-client-secret');
    Config::set('services.google.redirect', 'http://plann.test/auth/google/callback');

    $googleUser = Mockery::mock(SocialiteUser::class);
    $googleUser->shouldReceive('getId')->andReturn('google-123');
    $googleUser->shouldReceive('getEmail')->andReturn('google@example.test');
    $googleUser->shouldReceive('getName')->andReturn('Google User');
    $googleUser->shouldReceive('getNickname')->andReturn(null);
    $googleUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.test/avatar.png');

    Socialite::shouldReceive('driver->user')
        ->once()
        ->andReturn($googleUser);

    get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard'));

    assertAuthenticated();

    assertDatabaseHas('tenants', [
        'name' => 'Google User',
    ]);

    $tenant = Tenant::query()->where('name', 'Google User')->firstOrFail();

    assertDatabaseHas('users', [
        'tenant_id' => $tenant->id,
        'name' => 'Google User',
        'email' => 'google@example.test',
        'google_id' => 'google-123',
    ]);

    expect(User::query()->where('email', 'google@example.test')->firstOrFail()->password)->not->toBeNull();
});

test('a google signup is verified without a pin round-trip', function (): void {
    Config::set('services.google.client_id', 'google-client-id');
    Config::set('services.google.client_secret', 'google-client-secret');
    Config::set('services.google.redirect', 'http://plann.test/auth/google/callback');

    Notification::fake();

    $googleUser = Mockery::mock(SocialiteUser::class);
    $googleUser->shouldReceive('getId')->andReturn('google-verified-1');
    $googleUser->shouldReceive('getEmail')->andReturn('verified@example.test');
    $googleUser->shouldReceive('getName')->andReturn('Verified User');
    $googleUser->shouldReceive('getNickname')->andReturn(null);
    $googleUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->user')->once()->andReturn($googleUser);

    // Google already proved ownership, so the app opens straight away.
    get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'verified@example.test')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();

    actingAs($user)->get('/')->assertSuccessful();
});
