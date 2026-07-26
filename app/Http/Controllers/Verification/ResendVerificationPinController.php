<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResendVerificationPinController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null || $user->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', __('auth.ui.verify.resent'));
    }
}
