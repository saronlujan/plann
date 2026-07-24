<?php

namespace App\Http\Controllers\Register;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Register/Register', [
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
