<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()?->update($request->validated());

        return back();
    }
}
