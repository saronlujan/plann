<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Http\Requests\Login\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    /**
     * Failed attempts allowed per email+IP pair before the throttle kicks in.
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $throttleKey = $this->throttleKey($validated['email'], $request->ip());

        $this->ensureIsNotRateLimited($throttleKey);

        $authenticated = Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ], (bool) ($validated['remember'] ?? false));

        if (! $authenticated) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return back()->withErrors([
                'email' => __('auth.ui.errors.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return to_route('dashboard');
    }

    /**
     * Throttle per email+IP so an attacker cannot lock a legitimate user out of
     * their own account, while a single IP still cannot spray many accounts.
     */
    private function throttleKey(string $email, ?string $ip): string
    {
        return 'login|'.Str::transliterate(Str::lower($email)).'|'.($ip ?? 'unknown');
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(string $throttleKey): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.ui.errors.throttled', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ]),
        ]);
    }
}
