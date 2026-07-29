<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\Transactions\TransactionAttachments;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowTransactionAttachmentController extends Controller
{
    public function __construct(private readonly TransactionAttachments $attachments) {}

    /**
     * Stream a receipt from the private disk after checking the caller owns the
     * transaction. The file is never reachable by a public URL.
     *
     * Served as a download rather than inline: the file is user-supplied content
     * on the application's own origin, so rendering it in the page context would
     * be an unnecessary XSS surface.
     */
    public function __invoke(Transaction $transaction): StreamedResponse
    {
        $this->authorize('view', $transaction);

        abort_if($transaction->attachment === null, 404);

        // Built from the row's own tenant, not the request's: the folder can only
        // ever be the one that owns the file.
        $path = $this->attachments->path($transaction->attachment, $transaction->tenant_id);
        $disk = $this->attachments->disk();

        abort_unless($disk->exists($path), 404);

        return $disk->download(
            $path,
            $transaction->attachment,
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }
}
