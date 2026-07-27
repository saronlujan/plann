<?php

namespace App\Support\Reports;

use App\Enums\TransactionMovementType;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Aggregates projected entries into the report figures.
 *
 * It works off TransactionProjector output rather than raw rows so recurring and
 * installment transactions are counted once per occurrence, exactly as the user
 * sees them on screen. Every total is computed with bcmath: a report that does
 * not tie out to the transaction list is worse than no report at all.
 */
class ReportBuilder
{
    public function __construct(private readonly TransactionProjector $projector) {}

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Category>  $categories
     * @return array{
     *     summary: array<string, string>,
     *     monthly: array<int, array<string, string>>,
     *     by_category: array<int, array<string, string>>,
     *     by_account: array<int, array<string, string>>
     * }
     */
    public function build(
        Collection $transactions,
        Collection $categories,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $monthly = [];
        $entries = collect();

        $period = $from->startOfMonth();
        $lastPeriod = $to->startOfMonth();

        while ($period->lessThanOrEqualTo($lastPeriod)) {
            $monthEntries = $this->projector->entriesForPeriod($transactions, $period);

            $income = $this->sum($monthEntries, TransactionMovementType::Income);
            $expenses = $this->sum($monthEntries, TransactionMovementType::Expense);

            $monthly[] = [
                'month' => $period->format('Y-m'),
                'label' => $period->isoFormat('MMM/YY'),
                'income' => $income,
                'expenses' => $expenses,
                'net' => bcsub($income, $expenses, 2),
            ];

            $entries = $entries->merge($monthEntries);
            $period = $period->addMonth();
        }

        return [
            'summary' => $this->summary($entries),
            'monthly' => $monthly,
            'by_category' => $this->byCategory($entries, $categories),
            'by_account' => $this->byAccount($entries),
        ];
    }

    /**
     * Expected covers every projected entry; realized only the settled ones. The
     * gap between them is what tells the user how much is still outstanding.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array<string, string>
     */
    private function summary(Collection $entries): array
    {
        $income = $this->sum($entries, TransactionMovementType::Income);
        $expenses = $this->sum($entries, TransactionMovementType::Expense);
        $incomePaid = $this->sum($entries, TransactionMovementType::Income, onlyPaid: true);
        $expensesPaid = $this->sum($entries, TransactionMovementType::Expense, onlyPaid: true);

        return [
            'income' => $income,
            'expenses' => $expenses,
            'net' => bcsub($income, $expenses, 2),
            'income_paid' => $incomePaid,
            'expenses_paid' => $expensesPaid,
            'net_paid' => bcsub($incomePaid, $expensesPaid, 2),
            'entries' => (string) $entries->reject($this->isTransfer(...))->count(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @param  Collection<int, Category>  $categories
     * @return array<int, array<string, string>>
     */
    private function byCategory(Collection $entries, Collection $categories): array
    {
        $rows = [];

        foreach ([TransactionMovementType::Expense, TransactionMovementType::Income] as $movement) {
            $movementTotal = $this->sum($entries, $movement);

            $grouped = $entries
                ->reject($this->isTransfer(...))
                ->filter(fn (array $entry): bool => $entry['movement_type'] === $movement->value)
                ->groupBy(fn (array $entry): string => (string) ($entry['category_id'] ?? ''));

            foreach ($grouped as $key => $group) {
                $category = $key === '' ? null : $categories->get((int) $key);
                $total = $this->total($group);

                $rows[] = [
                    'type' => $movement->value,
                    'name' => $category->name ?? __('reports.uncategorized'),
                    'color' => $category?->color->value ?? 'zinc',
                    'total' => $total,
                    'share' => $this->share($total, $movementTotal),
                ];
            }
        }

        usort($rows, fn (array $a, array $b): int => bccomp($b['total'], $a['total'], 2));

        return $rows;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array<int, array<string, string>>
     */
    private function byAccount(Collection $entries): array
    {
        $rows = $entries
            ->reject($this->isTransfer(...))
            ->groupBy(fn (array $entry): string => (string) ($entry['source'] ?? ''))
            ->map(function (Collection $group, string $account): array {
                $income = $this->sum($group, TransactionMovementType::Income);
                $expenses = $this->sum($group, TransactionMovementType::Expense);

                return [
                    'name' => $account === '' ? __('reports.no_account') : $account,
                    'income' => $income,
                    'expenses' => $expenses,
                    'net' => bcsub($income, $expenses, 2),
                ];
            })
            ->values()
            ->all();

        usort($rows, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * Transfers move money between the user's own accounts: counting them would
     * inflate both sides of every total.
     *
     * @param  array<string, mixed>  $entry
     */
    private function isTransfer(array $entry): bool
    {
        return $entry['is_transfer'] === true;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return numeric-string
     */
    private function sum(Collection $entries, TransactionMovementType $movement, bool $onlyPaid = false): string
    {
        return $this->total(
            $entries
                ->reject($this->isTransfer(...))
                ->filter(fn (array $entry): bool => $entry['movement_type'] === $movement->value)
                ->filter(fn (array $entry): bool => ! $onlyPaid || $entry['paid_at'] !== null)
        );
    }

    /**
     * Declared as iterable, not Collection: the method only walks the entries, and
     * Collection's value type is invariant — a filtered collection would not be
     * accepted even though it is a perfectly valid input.
     *
     * @param  iterable<array<string, mixed>>  $entries
     * @return numeric-string
     */
    private function total(iterable $entries): string
    {
        $total = '0.00';

        foreach ($entries as $entry) {
            $amount = (string) $entry['amount'];

            if (is_numeric($amount)) {
                $total = bcadd($total, $amount, 2);
            }
        }

        return $total;
    }

    /**
     * @param  numeric-string  $value
     * @param  numeric-string  $total
     * @return numeric-string
     */
    private function share(string $value, string $total): string
    {
        if (bccomp($total, '0.00', 2) === 0) {
            return '0.00';
        }

        return bcdiv(bcmul($value, '100', 4), $total, 2);
    }
}
