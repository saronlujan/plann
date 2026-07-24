<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Login/Login', [
            'googleOAuthEnabled' => $this->isGoogleOAuthEnabled(),
        ]);
    }

    private function isGoogleOAuthEnabled(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
