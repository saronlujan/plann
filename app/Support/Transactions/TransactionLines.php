<?php

namespace App\Support\Transactions;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * The breakdown of a transaction into the services it was made of.
 *
 * Five code paths write a transaction — a plain entry, both legs of a transfer,
 * a single occurrence of a series and a series split in two — and every one of
 * them has to keep the same promise: the lines add up to the amount. That is why
 * both halves of it live here rather than being copied five times.
 */
class TransactionLines
{
    /**
     * What the transaction is worth once its breakdown is taken into account.
     *
     * A transaction that was broken down is worth exactly the sum of its parts,
     * so the total the form sent is recomputed rather than trusted: the field is
     * read-only there, and taking it at face value would let a crafted request
     * record a total its own lines contradict.
     *
     * @param  array<string, mixed>  $validated
     * @return numeric-string
     */
    public function amountFor(array $validated): string
    {
        $lines = $this->linesFrom($validated);

        if ($lines === []) {
            return $this->money($validated['amount'] ?? 0);
        }

        return array_reduce(
            $lines,
            fn (string $total, array $line): string => bcadd($total, $line['amount'], 2),
            '0.00',
        );
    }

    /**
     * Replaces the breakdown with what the request carried.
     *
     * A request that never mentions services leaves the existing lines alone, so
     * an edit that has nothing to do with them cannot quietly erase the split.
     *
     * @param  array<string, mixed>  $validated
     */
    public function sync(Transaction $transaction, array $validated): void
    {
        if (! array_key_exists('services', $validated)) {
            return;
        }

        $lines = $this->linesFrom($validated);

        // A line has no identity of its own — it is only a service and a number —
        // so the whole set is rewritten rather than reconciled row by row.
        DB::table('service_transaction')
            ->where('tenant_id', $transaction->tenant_id)
            ->where('transaction_id', $transaction->id)
            ->delete();

        if ($lines === []) {
            return;
        }

        $now = now();

        DB::table('service_transaction')->insert(array_map(fn (array $line): array => [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->id,
            'service_id' => $line['service_id'],
            'amount' => $line['amount'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $lines));
    }

    /**
     * The lines a request is asking for, normalised.
     *
     * Naming the same service twice is folded into one line rather than rejected:
     * the form does not offer it, and the pivot's unique would turn a slip into a
     * 500. Lines with no service are the ones left behind by a retired service —
     * they carry real money, so they survive the round trip and stay separate.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, array{service_id: int|null, amount: numeric-string}>
     */
    private function linesFrom(array $validated): array
    {
        /** @var array<int, array<string, mixed>> $submitted */
        $submitted = $validated['services'] ?? [];

        $attributed = [];
        $unattributed = [];

        foreach ($submitted as $line) {
            $amount = $this->money($line['amount'] ?? 0);
            $serviceId = isset($line['service_id']) ? (int) $line['service_id'] : null;

            if ($serviceId === null) {
                $unattributed[] = ['service_id' => null, 'amount' => $amount];

                continue;
            }

            $attributed[$serviceId] = isset($attributed[$serviceId])
                ? bcadd($attributed[$serviceId], $amount, 2)
                : $amount;
        }

        $lines = [];

        foreach ($attributed as $serviceId => $amount) {
            $lines[] = ['service_id' => $serviceId, 'amount' => $amount];
        }

        return [...$lines, ...$unattributed];
    }

    /**
     * A validated amount as the fixed-point string the rest of the money handling
     * expects, so both halves of the sum are shaped the same way.
     *
     * @return numeric-string
     */
    private function money(mixed $value): string
    {
        return sprintf('%.2f', (float) $value);
    }
}
