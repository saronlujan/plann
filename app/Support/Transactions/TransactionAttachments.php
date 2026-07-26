<?php

namespace App\Support\Transactions;

use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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

    public function __construct(private readonly TenantContext $tenantContext) {}

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /**
     * Store an upload under the current tenant's folder and return its path.
     *
     * @throws RuntimeException when the disk rejects the write — losing a receipt
     *                          silently is worse than failing the request.
     */
    public function store(?UploadedFile $attachment): ?string
    {
        if ($attachment === null) {
            return null;
        }

        $path = $this->disk()->putFile($this->directory(), $attachment);

        if ($path === false) {
            throw new RuntimeException('Could not store the transaction attachment.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path === null) {
            return;
        }

        $this->disk()->delete($path);
    }

    /**
     * Remove the previously stored file once a new attachment has replaced it.
     */
    public function discardReplaced(?UploadedFile $newAttachment, ?string $previousPath, ?string $currentPath): void
    {
        if ($newAttachment === null || $previousPath === null || $previousPath === $currentPath) {
            return;
        }

        $this->delete($previousPath);
    }

    /**
     * Namespacing by tenant keeps one workspace's files out of another's folder,
     * independent of the authorization check on the download route.
     */
    private function directory(): string
    {
        $tenantId = $this->tenantContext->tenantId();

        return $tenantId === null ? self::DIRECTORY : self::DIRECTORY.'/'.$tenantId;
    }
}
