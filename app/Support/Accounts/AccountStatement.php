<?php

namespace App\Support\Accounts;

use App\Enums\TransactionMovementType;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Computes account balances and per-period statements (with a running balance)
 * from projected transaction entries. Balances are "booked": every movement
 * dated up to the reference point counts, matching how the app plans cash flow.
 */
class AccountStatement
{
    public function __construct(private TransactionProjector $projector) {}

    /**
     * Balance = initial balance + net of every entry dated on/before $date.
     *
     * @param  Collection<int, Transaction>  $transactions
     */
    public function balanceAsOf(Account $account, Collection $transactions, CarbonImmutable $date): string
    {
        $earliest = $this->earliest($transactions);
        $net = 0.0;

        if ($earliest !== null) {
            $net = $this->entriesBetween($transactions, $earliest, $date)
                ->filter(fn (array $entry): bool => $entry['date'] <= $date->toDateString())
                ->reduce(fn (float $carry, array $entry): float => $carry + $this->signed($entry), 0.0);
        }

        return $this->money((float) $account->balance + $net);
    }

    /**
     * Statement for a month: opening/closing balance, income/expense totals and
     * each movement with the running balance after it.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array{opening: string, closing: string, income: string, expense: string, entries: array<int, array<string, mixed>>}
     */
    public function forPeriod(Account $account, Collection $transactions, CarbonImmutable $period): array
    {
        $periodStart = $period->startOfMonth();
        $earliest = $this->earliest($transactions);

        $netBefore = 0.0;

        if ($earliest !== null && $earliest->lessThan($periodStart)) {
            $netBefore = $this->entriesBetween($transactions, $earliest, $periodStart->subDay())
                ->filter(fn (array $entry): bool => $entry['date'] < $periodStart->toDateString())
                ->reduce(fn (float $carry, array $entry): float => $carry + $this->signed($entry), 0.0);
        }

        $opening = (float) $account->balance + $netBefore;
        $running = $opening;
        $income = 0.0;
        $expense = 0.0;

        $entries = $this->projector->entriesForPeriod($transactions, $periodStart)
            ->sortBy([['date', 'asc'], ['id', 'asc']])
            ->values()
            ->map(function (array $entry) use (&$running, &$income, &$expense): array {
                $amount = (float) $entry['amount'];
                $isIncome = $entry['movement_type'] === TransactionMovementType::Income->value;

                // Transfers move money (affect the running balance) but are not
                // income/expense, so they are excluded from the period totals.
                if ($isIncome) {
                    $running += $amount;

                    if (! $entry['is_transfer']) {
                        $income += $amount;
                    }
                } else {
                    $running -= $amount;

                    if (! $entry['is_transfer']) {
                        $expense += $amount;
                    }
                }

                return [
                    'id' => $entry['id'],
                    'date' => $entry['date'],
                    'description' => $entry['description'],
                    'movement_type' => $entry['movement_type'],
                    'category_id' => $entry['category_id'],
                    'amount' => $this->money($amount),
                    'paid' => $entry['paid_at'] !== null,
                    'balance' => $this->money($running),
                ];
            })
            ->all();

        return [
            'opening' => $this->money($opening),
            'closing' => $this->money($running),
            'income' => $this->money($income),
            'expense' => $this->money($expense),
            'entries' => $entries,
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, array<string, mixed>>
     */
    private function entriesBetween(Collection $transactions, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $entries = collect();
        $period = $from->startOfMonth();
        $end = $to->startOfMonth();

        while ($period->lessThanOrEqualTo($end)) {
            $entries = $entries->merge($this->projector->entriesForPeriod($transactions, $period));
            $period = $period->addMonth();
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function signed(array $entry): float
    {
        $amount = (float) $entry['amount'];

        return $entry['movement_type'] === TransactionMovementType::Income->value ? $amount : -$amount;
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function earliest(Collection $transactions): ?CarbonImmutable
    {
        $min = $transactions
            ->map(fn (Transaction $transaction): string => $transaction->effective_date->toDateString())
            ->min();

        return $min === null ? null : CarbonImmutable::parse($min);
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
