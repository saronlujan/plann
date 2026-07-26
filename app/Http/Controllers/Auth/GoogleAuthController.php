<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(private readonly RegisterUser $registerUser) {}

    public function redirect(): SymfonyRedirectResponse
    {
        if (! $this->isConfigured()) {
            return to_route('login')->with('error', 'Google login is not configured yet.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return to_route('login')->with('error', 'Google login is not configured yet.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            // Consent denied, an expired state token or a Google outage. None of
            // these are actionable by the user beyond retrying the login.
            report($exception);

            return to_route('login')->withErrors(['email' => __('auth.ui.social.google_failed')]);
        }

        $user = $this->resolveUser($googleUser);

        Auth::login($user);

        $request->session()->regenerate();

        return to_route('dashboard');
    }

    private function resolveUser(SocialiteUser $googleUser): User
    {
        $email = $googleUser->getEmail();

        abort_unless($email !== null, 422, 'Google account did not provide an email address.');

        $existingUser = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($existingUser !== null) {
            $existingUser->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar() ?: $existingUser->avatar_url,
                // Google already proved ownership of this address.
                'email_verified_at' => $existingUser->email_verified_at ?? now(),
            ])->save();

            return $existingUser;
        }

        $user = $this->registerUser->handle([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $email,
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar() ?: null,
            'password' => Str::password(32),
        ]);

        // No PIN round-trip: the address is confirmed by the provider itself.
        $user->markEmailAsVerified();

        return $user;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
