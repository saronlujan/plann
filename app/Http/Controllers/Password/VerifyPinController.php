<?php

namespace App\Http\Controllers\Password;

use App\Actions\Auth\SendPasswordResetPin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Password\VerifyPinRequest;
use App\Models\User;
use App\Models\UserPin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyPinController extends Controller
{
    /**
     * Step 1 of the reset: confirm the PIN. On success the verified PIN id is
     * kept in the session so the reset page can unlock the new-password fields.
     */
    public function __invoke(VerifyPinRequest $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');
        $user = $email === null ? null : User::query()->where('email', $email)->first();

        $pin = $user === null ? null : UserPin::query()
            ->where('user_id', $user->id)
            ->where('purpose', SendPasswordResetPin::PURPOSE)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($user === null || $pin === null || ! Hash::check($request->validated()['pin'], $pin->pin)) {
            throw ValidationException::withMessages([
                'pin' => __('auth.ui.reset.invalid_pin'),
            ]);
        }

        $request->session()->put('password_reset_pin_id', $pin->id);

        return redirect()->route('password.reset');
    }
}
