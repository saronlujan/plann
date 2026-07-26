<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadProfileController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Index', [
            'profile' => [
                'name' => $user?->name,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ],
        ]);
    }
}
