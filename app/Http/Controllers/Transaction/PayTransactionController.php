<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

class PayTransactionController extends Controller
{
    public function __invoke(string $transaction): RedirectResponse
    {
        $transaction = Transaction::query()->findOrFail($transaction);

        $transaction->update([
            'paid_at' => now()->toDateString(),
        ]);

        return redirect()->route('transactions.index', [
            'period' => CarbonImmutable::parse($transaction->effective_date->toDateString())->format('Y-m'),
        ]);
    }
}
