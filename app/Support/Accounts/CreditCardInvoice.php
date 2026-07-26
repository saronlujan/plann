<?php

namespace App\Support\Accounts;

use App\Enums\TransactionMovementType;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Computes the current open invoice of a credit card (billing cycle bounded by
 * the closing day), plus outstanding debt and available limit.
 */
class CreditCardInvoice
{
    public function __construct(private TransactionProjector $projector) {}

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return array{cycle_start: string, cycle_end: string, due_date: string, total: string, outstanding: string, available: ?string, entries: array<int, array<string, mixed>>}
     */
    public function current(Account $card, Collection $transactions, CarbonImmutable $today): array
    {
        $closingThisMonth = $this->dayOfMonth($today, $card->closing_day ?? 1);

        $cycleEnd = $today->lessThanOrEqualTo($closingThisMonth)
            ? $closingThisMonth
            : $this->dayOfMonth($today->addMonth(), $card->closing_day ?? 1);

        $cycleStart = $this->dayOfMonth($cycleEnd->subMonth(), $card->closing_day ?? 1)->addDay();
        $dueDate = $this->dueDate($cycleEnd, $card->due_day ?? 1);

        $entries = $this->entriesBetween($transactions, $cycleStart, $cycleEnd)
            ->filter(fn (array $entry): bool => $entry['date'] >= $cycleStart->toDateString()
                && $entry['date'] <= $cycleEnd->toDateString()
                && $entry['movement_type'] === TransactionMovementType::Expense->value
                && $entry['is_transfer'] === false)
            ->sortBy([['date', 'asc'], ['id', 'asc']])
            ->values()
            ->map(fn (array $entry): array => [
                'id' => $entry['id'],
                'date' => $entry['date'],
                'description' => $entry['description'],
                'category_id' => $entry['category_id'],
                'amount' => $this->money((float) $entry['amount']),
            ]);

        $total = $entries->reduce(fn (float $carry, array $entry): float => $carry + (float) $entry['amount'], 0.0);
        $outstanding = $this->outstanding($transactions, $today);
        $available = $card->credit_limit === null
            ? null
            : $this->money((float) $card->credit_limit - $outstanding);

        return [
            'cycle_start' => $cycleStart->toDateString(),
            'cycle_end' => $cycleEnd->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'total' => $this->money($total),
            'outstanding' => $this->money($outstanding),
            'available' => $available,
            'entries' => $entries->all(),
        ];
    }

    /**
     * Debt still owed today = purchases − payments across the whole card history.
     *
     * @param  Collection<int, Transaction>  $transactions
     */
    private function outstanding(Collection $transactions, CarbonImmutable $today): float
    {
        $earliest = $transactions
            ->map(fn (Transaction $transaction): string => $transaction->effective_date->toDateString())
            ->min();

        if ($earliest === null) {
            return 0.0;
        }

        return $this->entriesBetween($transactions, CarbonImmutable::parse($earliest), $today)
            ->filter(fn (array $entry): bool => $entry['date'] <= $today->toDateString())
            ->reduce(function (float $carry, array $entry): float {
                $amount = (float) $entry['amount'];

                return $entry['movement_type'] === TransactionMovementType::Expense->value
                    ? $carry + $amount
                    : $carry - $amount;
            }, 0.0);
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

    private function dayOfMonth(CarbonImmutable $reference, int $day): CarbonImmutable
    {
        $safeDay = min($day, $reference->daysInMonth);

        return $reference->startOfMonth()->addDays($safeDay - 1);
    }

    private function dueDate(CarbonImmutable $closing, int $dueDay): CarbonImmutable
    {
        $candidate = $this->dayOfMonth($closing, $dueDay);

        return $candidate->lessThan($closing)
            ? $this->dayOfMonth($closing->addMonth(), $dueDay)
            : $candidate;
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
