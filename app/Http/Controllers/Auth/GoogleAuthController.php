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

class GoogleAuthController extends Controller
{
    public function __construct(private readonly RegisterUser $registerUser) {}

    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return to_route('login')->with('error', 'Google login is not configured yet.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

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
            ])->save();

            return $existingUser;
        }

        return $this->registerUser->handle([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $email,
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar() ?: null,
            'password' => Str::password(32),
        ]);
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
