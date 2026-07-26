<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPin;
use App\Notifications\PasswordResetPinNotification;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\withoutMiddleware;

function resetUser(string $email): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);

    return User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);
}

function usablePin(User $user, string $pin = '123456'): UserPin
{
    return UserPin::create([
        'user_id' => $user->id,
        'purpose' => 'password_reset',
        'pin' => Hash::make($pin),
        'expires_at' => now()->addMinutes(10),
    ]);
}

beforeEach(function () {
    withoutMiddleware(ThrottleRequests::class);
});

test('the forgot-password page renders', function () {
    $this->get('/forgot-password')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->component('Auth/ForgotPassword'));
});

test('requesting a reset issues a pin, emails it and stores the email in session', function () {
    Notification::fake();
    $user = resetUser('reset-pin@example.com');

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHas('password_reset_email', $user->email);

    expect(UserPin::query()->where('user_id', $user->id)->exists())->toBeTrue();
    Notification::assertSentTo($user, PasswordResetPinNotification::class);
});

test('an unknown email does not issue a pin but responds the same', function () {
    Notification::fake();

    $this->post('/forgot-password', ['email' => 'nobody@example.com'])
        ->assertRedirect(route('password.reset'));

    expect(UserPin::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

test('verifying a valid pin unlocks the reset step', function () {
    $user = resetUser('reset-verify@example.com');
    $pin = usablePin($user, '123456');

    $this->withSession(['password_reset_email' => $user->email])
        ->post('/reset-password/verify', ['pin' => '123456'])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHas('password_reset_pin_id', $pin->id);
});

test('an invalid pin is rejected at verification', function () {
    $user = resetUser('reset-bad@example.com');
    usablePin($user, '654321');

    $this->withSession(['password_reset_email' => $user->email])
        ->post('/reset-password/verify', ['pin' => '111111'])
        ->assertSessionHasErrors('pin')
        ->assertSessionMissing('password_reset_pin_id');
});

test('an expired pin is rejected at verification', function () {
    $user = resetUser('reset-exp@example.com');
    UserPin::create([
        'user_id' => $user->id,
        'purpose' => 'password_reset',
        'pin' => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
    ]);

    $this->withSession(['password_reset_email' => $user->email])
        ->post('/reset-password/verify', ['pin' => '123456'])
        ->assertSessionHasErrors('pin');
});

test('setting a new password after verification works', function () {
    $user = resetUser('reset-ok@example.com');
    $pin = usablePin($user, '123456');

    $this->withSession([
        'password_reset_email' => $user->email,
        'password_reset_pin_id' => $pin->id,
    ])
        ->post('/reset-password', [
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
        ->assertRedirect(route('login'));

    expect(Hash::check('new-strong-password', $user->refresh()->password))->toBeTrue();
    expect($pin->fresh()?->consumed_at)->not->toBeNull();
});

test('resetting requires a verified pin', function () {
    $user = resetUser('reset-unverified@example.com');
    usablePin($user, '123456');

    $this->withSession(['password_reset_email' => $user->email])
        ->post('/reset-password', [
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
        ->assertSessionHasErrors('pin');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
