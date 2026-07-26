<?php

namespace App\Http\Controllers\Password;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ShowForgotPasswordController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }
}
