<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Models\User;
use App\Support\Profile\AvatarStorage;
use Illuminate\Http\RedirectResponse;

class UpdateAvatarController extends Controller
{
    public function __construct(private readonly AvatarStorage $avatars) {}

    public function __invoke(UpdateAvatarRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $previous = $user->avatar;

        $fileName = $this->avatars->store($user, $request->file('avatar'), [
            'x' => $request->integer('crop_x'),
            'y' => $request->integer('crop_y'),
            'size' => $request->integer('crop_size'),
        ]);

        $user->forceFill(['avatar' => $fileName])->save();

        // Only after the new one is safely stored: a failed write must not leave
        // the account without a picture.
        $this->avatars->delete($user, $previous);

        return back();
    }
}
