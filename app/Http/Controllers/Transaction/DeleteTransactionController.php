<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DeleteTransactionController extends Controller
{
    public function __invoke(Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $period = $transaction->effective_date->format('Y-m');

        if ($transaction->attachment_path !== null) {
            Storage::disk('public')->delete($transaction->attachment_path);
        }

        $transaction->delete();

        return to_route('transactions.index', ['period' => $period]);
    }
}
