<?php

namespace App\Http\Controllers\Password;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowResetPasswordController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            // Whether the PIN was already verified — unlocks the password fields.
            'verified' => $request->session()->has('password_reset_pin_id'),
        ]);
    }
}
