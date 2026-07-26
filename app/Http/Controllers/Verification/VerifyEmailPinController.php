<?php

namespace App\Http\Controllers\Verification;

use App\Actions\Auth\SendEmailVerificationPin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Verification\VerifyEmailPinRequest;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyEmailPinController extends Controller
{
    public function __invoke(VerifyEmailPinRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        if ($user->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        $pin = UserPin::query()
            ->where('user_id', $user->id)
            ->where('purpose', SendEmailVerificationPin::PURPOSE)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($pin === null || ! Hash::check($request->validated()['pin'], $pin->pin)) {
            throw ValidationException::withMessages([
                'pin' => __('auth.ui.verify.invalid_pin'),
            ]);
        }

        $user->markEmailAsVerified();
        $pin->update(['consumed_at' => now()]);

        Event::dispatch(new Verified($user));

        return to_route('dashboard');
    }
}
