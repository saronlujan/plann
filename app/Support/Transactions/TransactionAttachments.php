<?php

namespace App\Support\Transactions;

use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageManagerInterface;
use RuntimeException;
use Throwable;

/**
 * Single entry point for transaction attachment storage.
 *
 * Receipts are financial documents, so they live on a private disk and are only
 * ever served through an authorized route — never by a public URL.
 */
class TransactionAttachments
{
    public const DISK = 'local';

    private const DIRECTORY = 'transactions/attachments';

    /**
     * Receipts only ever need to be legible, never printable at full size. A
     * phone photo arrives at 3000px and several megabytes; this keeps the text
     * readable at a fraction of the storage bill.
     */
    private const MAX_DIMENSION = 1600;

    private const WEBP_QUALITY = 82;

    /** Uploads worth re-encoding. PDFs and office files are stored untouched. */
    private const RESIZABLE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ImageManagerInterface $images,
    ) {}

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /**
     * Store an upload under the current tenant's folder and return its file name.
     *
     * Only the name is persisted: the folder is derived from the owning tenant,
     * so a stored row cannot point at another workspace's directory, and moving
     * the storage layout later touches this class alone.
     *
     * @throws RuntimeException when the disk rejects the write — losing a receipt
     *                          silently is worse than failing the request.
     */
    public function store(?UploadedFile $attachment): ?string
    {
        if ($attachment === null) {
            return null;
        }

        $optimized = $this->optimize($attachment);

        if ($optimized !== null) {
            $fileName = Str::random(40).'.webp';

            if (! $this->disk()->put($this->path($fileName), $optimized)) {
                throw new RuntimeException('Could not store the transaction attachment.');
            }

            return $fileName;
        }

        $path = $this->disk()->putFile($this->directory(), $attachment);

        if ($path === false) {
            throw new RuntimeException('Could not store the transaction attachment.');
        }

        return basename($path);
    }

    /**
     * Where a stored file lives on the disk.
     *
     * Pass the owning tenant when serving somebody else's row is possible; it
     * defaults to the request's tenant, which is what an upload always wants.
     */
    public function path(string $fileName, ?int $tenantId = null): string
    {
        return $this->directory($tenantId).'/'.$fileName;
    }

    /**
     * Downscale and re-encode an image upload, or null when it is not an image.
     *
     * Orientation is applied first: phones record it in EXIF rather than in the
     * pixels, and re-encoding drops the metadata — without this, receipts would
     * come out sideways.
     */
    private function optimize(UploadedFile $attachment): ?string
    {
        if (! in_array((string) $attachment->getMimeType(), self::RESIZABLE_MIME_TYPES, true)) {
            return null;
        }

        try {
            return (string) $this->images
                ->decodePath($attachment->getRealPath())
                ->orient()
                ->scaleDown(self::MAX_DIMENSION, self::MAX_DIMENSION)
                ->encode(new WebpEncoder(quality: self::WEBP_QUALITY));
        } catch (Throwable) {
            // The mime type says image but the bytes disagree, or the encoder is
            // missing webp support. Keep the upload rather than lose a receipt.
            return null;
        }
    }

    public function delete(?string $fileName, ?int $tenantId = null): void
    {
        if ($fileName === null) {
            return;
        }

        $this->disk()->delete($this->path($fileName, $tenantId));
    }

    /**
     * Remove the previously stored file once a new attachment has replaced it.
     */
    public function discardReplaced(?UploadedFile $newAttachment, ?string $previousFileName, ?string $currentFileName): void
    {
        if ($newAttachment === null || $previousFileName === null || $previousFileName === $currentFileName) {
            return;
        }

        $this->delete($previousFileName);
    }

    /**
     * Namespacing by tenant keeps one workspace's files out of another's folder,
     * independent of the authorization check on the download route.
     */
    private function directory(?int $tenantId = null): string
    {
        $tenantId ??= $this->tenantContext->tenantId();

        return $tenantId === null ? self::DIRECTORY : self::DIRECTORY.'/'.$tenantId;
    }
}
