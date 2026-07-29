<?php

use App\Actions\Auth\SendEmailVerificationPin;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPin;
use App\Notifications\EmailVerificationPinNotification;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutMiddleware;

function unverifiedUser(string $email = 'unverified@example.com'): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    return User::factory()->unverified()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);
}

function verificationPin(User $user, string $pin = '123456', ?string $expiresAt = null): UserPin
{
    return UserPin::create([
        'user_id' => $user->id,
        'purpose' => SendEmailVerificationPin::PURPOSE,
        'pin' => Hash::make($pin),
        'expires_at' => $expiresAt ?? now()->addMinutes(30),
    ]);
}

beforeEach(function () {
    withoutMiddleware(ThrottleRequests::class);
});

test('an unverified user is redirected to the verification notice', function () {
    $user = unverifiedUser();

    actingAs($user)->get('/')->assertRedirect(route('verification.notice'));
    actingAs($user)->get('/transactions')->assertRedirect(route('verification.notice'));
    actingAs($user)->get('/accounts')->assertRedirect(route('verification.notice'));
    actingAs($user)->get('/billing')->assertRedirect(route('verification.notice'));
    actingAs($user)->get('/preferences')->assertRedirect(route('verification.notice'));
});

test('an unverified user may reach the verification page', function () {
    $user = unverifiedUser('sees-notice@example.com');

    actingAs($user)->get(route('verification.notice'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/VerifyEmail')
            ->where('email', 'sees-notice@example.com'));
});

test('a correct pin verifies the account and unlocks the app', function () {
    Event::fake([Verified::class]);

    $user = unverifiedUser('verifies@example.com');
    $pin = verificationPin($user, '123456');

    actingAs($user)
        ->post(route('verification.verify'), ['pin' => '123456'])
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    expect($pin->fresh()?->consumed_at)->not->toBeNull();

    Event::assertDispatched(Verified::class);

    // Any page behind the paywall proves it: the dashboard sends a workspace
    // with no account to the guided setup.
    actingAs($user)->get(route('accounts'))->assertSuccessful();
});

test('a wrong pin is rejected and leaves the account unverified', function () {
    $user = unverifiedUser('wrong-pin@example.com');
    verificationPin($user, '123456');

    actingAs($user)
        ->post(route('verification.verify'), ['pin' => '999999'])
        ->assertSessionHasErrors('pin');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('an expired pin is rejected', function () {
    $user = unverifiedUser('expired-pin@example.com');
    verificationPin($user, '123456', now()->subMinute()->toDateTimeString());

    actingAs($user)
        ->post(route('verification.verify'), ['pin' => '123456'])
        ->assertSessionHasErrors('pin');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('a consumed pin cannot be replayed', function () {
    $user = unverifiedUser('replay@example.com');
    $pin = verificationPin($user, '123456');

    actingAs($user)->post(route('verification.verify'), ['pin' => '123456'])->assertRedirect();

    // Force the account back to unverified: only the PIN state should block a replay.
    $user->forceFill(['email_verified_at' => null])->save();

    actingAs($user)
        ->post(route('verification.verify'), ['pin' => '123456'])
        ->assertSessionHasErrors('pin');

    expect($pin->fresh()?->consumed_at)->not->toBeNull();
    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('another users pin does not verify this account', function () {
    $victim = unverifiedUser('victim-pin@example.com');
    verificationPin($victim, '123456');

    $attacker = unverifiedUser('attacker-pin@example.com');

    actingAs($attacker)
        ->post(route('verification.verify'), ['pin' => '123456'])
        ->assertSessionHasErrors('pin');

    expect($attacker->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('resending issues a new pin and invalidates the previous one', function () {
    Notification::fake();

    $user = unverifiedUser('resend@example.com');
    $stale = verificationPin($user, '123456');

    actingAs($user)->post(route('verification.resend'))->assertRedirect();

    expect(UserPin::query()->whereKey($stale->id)->exists())->toBeFalse();
    expect(UserPin::query()
        ->where('user_id', $user->id)
        ->where('purpose', SendEmailVerificationPin::PURPOSE)
        ->count())->toBe(1);

    Notification::assertSentTo($user, EmailVerificationPinNotification::class);
});

test('the verification pin notification is queued and localized', function () {
    Notification::fake();

    $user = unverifiedUser('locale-pin@example.com');
    $user->update(['locale' => 'es']);

    actingAs($user)->post(route('verification.resend'));

    Notification::assertSentTo(
        $user,
        EmailVerificationPinNotification::class,
        fn ($notification): bool => $notification instanceof ShouldQueue && $notification->locale === 'es',
    );
});

test('an already verified user is sent to the dashboard', function () {
    $user = unverifiedUser('already@example.com');
    $user->markEmailAsVerified();

    actingAs($user)->get(route('verification.notice'))->assertRedirect(route('dashboard'));
    actingAs($user)->post(route('verification.verify'), ['pin' => '000000'])
        ->assertRedirect(route('dashboard'));
});

test('a password reset pin cannot be used to verify the email', function () {
    $user = unverifiedUser('cross-purpose@example.com');

    UserPin::create([
        'user_id' => $user->id,
        'purpose' => 'password_reset',
        'pin' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
    ]);

    actingAs($user)
        ->post(route('verification.verify'), ['pin' => '123456'])
        ->assertSessionHasErrors('pin');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('guests cannot reach the verification routes', function () {
    $this->get(route('verification.notice'))->assertRedirect(route('login'));
    $this->post(route('verification.verify'), ['pin' => '123456'])->assertRedirect(route('login'));
});
