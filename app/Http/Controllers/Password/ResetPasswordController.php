<?php

namespace App\Http\Controllers\Password;

use App\Http\Controllers\Controller;
use App\Http\Requests\Password\ResetPasswordRequest;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Http\RedirectResponse;

class ResetPasswordController extends Controller
{
    /**
     * Step 2 of the reset: set the new password. Only the PIN verified in the
     * previous step (stored in the session) is honoured; the form itself just
     * collects the new password.
     */
    public function __invoke(ResetPasswordRequest $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');
        $pinId = $request->session()->get('password_reset_pin_id');

        $user = $email === null ? null : User::query()->where('email', $email)->first();

        $pin = ($user === null || $pinId === null) ? null : UserPin::query()
            ->where('id', $pinId)
            ->where('user_id', $user->id)
            ->first();

        if ($user === null || $pin === null || ! $pin->isUsable()) {
            // The verification lapsed — send them back to the PIN step.
            $request->session()->forget('password_reset_pin_id');

            return redirect()->route('password.reset')->withErrors([
                'pin' => __('auth.ui.reset.invalid_pin'),
            ]);
        }

        // The 'password' cast on the User model hashes the value on save.
        $user->update(['password' => $request->validated()['password']]);
        $pin->update(['consumed_at' => now()]);
        $request->session()->forget(['password_reset_email', 'password_reset_pin_id']);

        return redirect()->route('login')->with('status', __('auth.ui.reset.success'));
    }
}
