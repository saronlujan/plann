<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;

class UpdatePasswordController extends Controller
{
    public function __invoke(UpdatePasswordRequest $request): RedirectResponse
    {
        // The 'password' cast on the User model hashes the value on save.
        $request->user()?->update(['password' => $request->validated()['password']]);

        return back();
    }
}
