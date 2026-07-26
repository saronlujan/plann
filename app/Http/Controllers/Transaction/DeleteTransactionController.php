<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\Transactions\TransactionAttachments;
use Illuminate\Http\RedirectResponse;

class DeleteTransactionController extends Controller
{
    public function __construct(private readonly TransactionAttachments $attachments) {}

    public function __invoke(Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $period = $transaction->effective_date->format('Y-m');

        $this->attachments->delete($transaction->attachment_path);

        $transaction->delete();

        return to_route('transactions.index', ['period' => $period]);
    }
}
