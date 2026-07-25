<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

class PayTransactionController extends Controller
{
    public function __invoke(Transaction $transaction): RedirectResponse
    {
        $this->authorize('pay', $transaction);

        $transaction->update([
            'paid_at' => $transaction->paid_at === null ? now()->toDateString() : null,
        ]);

        return to_route('transactions.index', [
            'period' => $transaction->effective_date->format('Y-m'),
        ]);
    }
}
