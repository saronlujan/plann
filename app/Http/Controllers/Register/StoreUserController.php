<?php

namespace App\Http\Controllers\Register;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Register\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoreUserController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $validated = $request->validated();

        $user = $registerUser->handle([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'country_code' => $validated['country_code'],
            'currency_code' => $validated['currency_code'],
            'password' => $validated['password'],
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        $user->sendEmailVerificationNotification();

        return to_route('verification.notice');
    }
}
