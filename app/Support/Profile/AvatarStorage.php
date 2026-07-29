<?php

namespace App\Support\Profile;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageManagerInterface;
use RuntimeException;

/**
 * Stores profile pictures, cropped to a square.
 *
 * The crop is applied here rather than trusted from the browser: the client can
 * only ask for a region, never hand over the bytes that get saved.
 */
class AvatarStorage
{
    public const DISK = 'local';

    private const DIRECTORY = 'avatars';

    /** Plenty for a header thumbnail and a retina profile picture. */
    private const SIZE = 512;

    private const WEBP_QUALITY = 85;

    public function __construct(private readonly ImageManagerInterface $images) {}

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /**
     * Crop, resize and store a picture, returning its file name.
     *
     * @param  array{x: int, y: int, size: int}  $crop  region in source pixels
     *
     * @throws RuntimeException when the image cannot be read or written — a
     *                          silently missing avatar looks like a lost upload.
     */
    public function store(User $user, UploadedFile $upload, array $crop): string
    {
        $image = $this->images->decodePath($upload->getRealPath());

        // Phones record orientation in EXIF; re-encoding drops it, so apply it
        // before cropping or the region would be taken from a rotated picture.
        $image->orient();

        $size = max(1, min($crop['size'], $image->width(), $image->height()));
        $x = max(0, min($crop['x'], $image->width() - $size));
        $y = max(0, min($crop['y'], $image->height() - $size));

        $encoded = (string) $image
            ->crop($size, $size, $x, $y)
            ->scaleDown(self::SIZE, self::SIZE)
            ->encode(new WebpEncoder(quality: self::WEBP_QUALITY));

        $fileName = Str::random(40).'.webp';

        if (! $this->disk()->put($this->path($user, $fileName), $encoded)) {
            throw new RuntimeException('Could not store the avatar.');
        }

        return $fileName;
    }

    /**
     * Where a stored avatar lives. Namespaced per user so one account's picture
     * can never sit in another's folder.
     */
    public function path(User $user, string $fileName): string
    {
        return self::DIRECTORY.'/'.$user->getKey().'/'.$fileName;
    }

    public function delete(User $user, ?string $fileName): void
    {
        if ($fileName === null) {
            return;
        }

        $this->disk()->delete($this->path($user, $fileName));
    }
}
