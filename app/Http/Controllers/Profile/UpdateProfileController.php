<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Profile\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfile $action): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $action->handle($user, $request->validated());
        }

        return back();
    }
}
