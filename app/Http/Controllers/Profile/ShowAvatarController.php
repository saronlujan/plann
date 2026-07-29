<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Profile\AvatarStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowAvatarController extends Controller
{
    public function __construct(private readonly AvatarStorage $avatars) {}

    /**
     * Stream the signed-in user's own picture.
     *
     * It lives on the private disk like every other upload, so it is served here
     * rather than by a public URL. The response is cacheable: the shared prop
     * carries a version parameter that changes whenever the file does.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_if($user->avatar === null, 404);

        $path = $this->avatars->path($user, $user->avatar);
        $disk = $this->avatars->disk();

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $user->avatar, [
            'Cache-Control' => 'private, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
