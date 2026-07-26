<?php

namespace App\Http\Controllers\Password;

use App\Actions\Auth\SendPasswordResetPin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Password\SendResetPinRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class SendResetPinController extends Controller
{
    public function __invoke(SendResetPinRequest $request, SendPasswordResetPin $action): RedirectResponse
    {
        $email = $request->validated()['email'];

        // Only issue a PIN for an existing account, but always respond the same
        // way so the endpoint never reveals whether an email is registered.
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            $action->handle($user);
        }

        // Carry the email server-side (session) so it never appears in the URL.
        $request->session()->put('password_reset_email', $email);

        return redirect()
            ->route('password.reset')
            ->with('status', __('auth.ui.forgot.sent'));
    }
}
