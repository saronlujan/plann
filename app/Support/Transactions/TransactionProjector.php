<?php

namespace App\Support\Transactions;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Projects stored transactions into the virtual monthly entries shown to the user.
 *
 * Recurring and installment transactions are stored as compact master rows and
 * expanded on demand for the requested period, keeping the persisted data small
 * while still rendering every occurrence.
 */
class TransactionProjector
{
    /**
     * Expand every transaction into the entries visible within the given period.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, array<string, mixed>>
     */
    public function entriesForPeriod(Collection $transactions, CarbonImmutable $period): Collection
    {
        $periodStart = $period->startOfMonth();
        $periodEnd = $period->endOfMonth();

        $adjustedRecurringPeriods = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type === TransactionType::Recurring && $transaction->adjustment_month !== null)
            ->groupBy(fn (Transaction $transaction): string => sprintf('%s:%s', $transaction->series_uuid ?? $transaction->id, $transaction->adjustment_month->format('Y-m')));

        $entries = collect();

        foreach ($transactions as $transaction) {
            if ($transaction->type === TransactionType::Unique) {
                if ($transaction->effective_date->betweenIncluded($periodStart, $periodEnd)) {
                    $entries->push($this->mapUniqueEntry($transaction, $periodStart));
                }

                continue;
            }

            if ($transaction->type === TransactionType::Recurring && $transaction->adjustment_month === null) {
                $adjustmentKey = sprintf('%s:%s', $transaction->series_uuid ?? $transaction->id, $period->format('Y-m'));

                if ($adjustedRecurringPeriods->has($adjustmentKey)) {
                    continue;
                }

                $isActiveInPeriod = $transaction->effective_date->lessThanOrEqualTo($periodEnd)
                    && ($transaction->effective_until === null || $transaction->effective_until->greaterThanOrEqualTo($periodStart));

                if ($isActiveInPeriod) {
                    $entries->push($this->mapRecurringEntry($transaction, $periodStart));
                }

                continue;
            }

            if ($transaction->type === TransactionType::Recurring && $transaction->adjustment_month?->isSameMonth($period)) {
                $entries->push($this->mapAdjustmentEntry($transaction, $period));

                continue;
            }

            if ($transaction->type === TransactionType::Installment) {
                $entries = $entries->merge($this->buildInstallmentEntries($transaction, $periodStart, $periodEnd));
            }
        }

        return $entries->sortBy([
            ['date', 'asc'],
            ['label', 'asc'],
        ])->values();
    }

    /**
     * Build realized and expected income/expense totals per currency.
     *
     * "Realized" only counts paid entries; "expected" counts every projected entry.
     * Currencies without entries in the period are omitted.
     *
     * @param  Collection<int, Currency>  $currencies
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    public function summaries(Collection $currencies, Collection $entries): Collection
    {
        return $currencies
            ->sortBy('code')
            ->values()
            ->map(function ($currency) use ($entries): ?array {
                $currencyEntries = $entries->where('currency_code', $currency->code);

                if ($currencyEntries->isEmpty()) {
                    return null;
                }

                $incomePaid = $this->sumMovement($currencyEntries, TransactionMovementType::Income, onlyPaid: true);
                $expensePaid = $this->sumMovement($currencyEntries, TransactionMovementType::Expense, onlyPaid: true);
                $incomeAll = $this->sumMovement($currencyEntries, TransactionMovementType::Income, onlyPaid: false);
                $expenseAll = $this->sumMovement($currencyEntries, TransactionMovementType::Expense, onlyPaid: false);

                return [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'income' => $incomePaid,
                    'expenses' => $expensePaid,
                    'total' => bcsub($incomePaid, $expensePaid, 2),
                    'expected_income' => $incomeAll,
                    'expected_expense' => $expenseAll,
                    'expected_total' => bcsub($incomeAll, $expenseAll, 2),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     */
    private function sumMovement(Collection $entries, TransactionMovementType $movement, bool $onlyPaid): string
    {
        return $entries
            ->filter(function (array $entry) use ($movement, $onlyPaid): bool {
                if ($entry['movement_type'] !== $movement->value) {
                    return false;
                }

                return $onlyPaid ? $entry['paid_at'] !== null : true;
            })
            ->reduce(fn (string $carry, array $entry): string => bcadd($carry, (string) $entry['amount'], 2), '0.00');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildInstallmentEntries(Transaction $transaction, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): Collection
    {
        $entries = collect();

        if ($transaction->installments_total === null || $transaction->installment_frequency === null) {
            return $entries;
        }

        $occurrenceDate = CarbonImmutable::parse($transaction->effective_date->toDateString());

        for ($installmentNumber = 1; $installmentNumber <= $transaction->installments_total; $installmentNumber++) {
            if ($occurrenceDate->betweenIncluded($periodStart, $periodEnd)) {
                $entries->push([
                    'id' => sprintf('transaction-%s-%s', $transaction->id, $installmentNumber),
                    'transaction_id' => $transaction->id,
                    'date' => $occurrenceDate->toDateString(),
                    'kind' => 'installment',
                    'type' => 'installment',
                    'schedule_type' => 'installment',
                    'movement_type' => $transaction->movement_type?->value ?? TransactionMovementType::Expense->value,
                    'label' => sprintf('%s - parcela %d/%d', $transaction->description, $installmentNumber, $transaction->installments_total),
                    'currency_code' => $transaction->currency->code,
                    'currency_symbol' => $transaction->currency->symbol,
                    'currency_id' => $transaction->currency_id,
                    'account_id' => $transaction->account_id,
                    'effective_date' => $transaction->effective_date->toDateString(),
                    'paid_at' => $transaction->paid_at?->toDateString(),
                    'adjustment_month' => $transaction->adjustment_month?->toDateString(),
                    'amount' => $this->formatMoney($transaction->amount),
                    'adjustment_amount' => $this->formatMoney($transaction->adjustment_amount),
                    'description' => $transaction->description,
                    'installment_frequency' => $transaction->installment_frequency?->value,
                    'installments_total' => $transaction->installments_total,
                    'installment_number' => $installmentNumber,
                    'source' => $this->resolveSourceLabel($transaction),
                ]);
            }

            $occurrenceDate = match ($transaction->installment_frequency) {
                TransactionInstallmentFrequency::Weekly => $occurrenceDate->addWeek(),
                TransactionInstallmentFrequency::Biweekly => $occurrenceDate->addWeeks(2),
                TransactionInstallmentFrequency::Bimonthly => $occurrenceDate->addMonthsNoOverflow(2),
                TransactionInstallmentFrequency::Semiannual => $occurrenceDate->addMonthsNoOverflow(6),
                TransactionInstallmentFrequency::Annual => $occurrenceDate->addYear(),
                default => $occurrenceDate->addMonthNoOverflow(),
            };
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUniqueEntry(Transaction $transaction, CarbonImmutable $periodStart): array
    {
        return $this->mapEntry($transaction, [
            'id' => sprintf('transaction-%s', $transaction->id),
            'date' => $this->clampDate($transaction->effective_date, $periodStart),
            'kind' => 'unique',
            'schedule_type' => TransactionType::Unique->value,
            'label' => $transaction->description,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRecurringEntry(Transaction $transaction, CarbonImmutable $periodStart): array
    {
        return $this->mapEntry($transaction, [
            'id' => sprintf('transaction-%s', $transaction->id),
            'date' => $this->clampDate($transaction->effective_date, $periodStart),
            'kind' => 'base',
            'schedule_type' => TransactionType::Recurring->value,
            'label' => sprintf('%s - recorrência', $transaction->description),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAdjustmentEntry(Transaction $transaction, CarbonImmutable $period): array
    {
        return $this->mapEntry($transaction, [
            'id' => sprintf('transaction-%s-adjustment', $transaction->id),
            'date' => $transaction->adjustment_month?->toDateString() ?? $period->toDateString(),
            'kind' => 'adjustment',
            'schedule_type' => TransactionType::Recurring->value,
            'label' => sprintf('%s - ajuste %s', $transaction->description, $period->format('m/Y')),
        ]);
    }

    /**
     * Merge the shared entry payload with the kind-specific overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function mapEntry(Transaction $transaction, array $overrides): array
    {
        return [
            'transaction_id' => $transaction->id,
            'type' => $overrides['schedule_type'],
            'movement_type' => $transaction->movement_type?->value ?? TransactionMovementType::Expense->value,
            'currency_code' => $transaction->currency->code,
            'currency_symbol' => $transaction->currency->symbol,
            'currency_id' => $transaction->currency_id,
            'account_id' => $transaction->account_id,
            'effective_date' => $transaction->effective_date->toDateString(),
            'paid_at' => $transaction->paid_at?->toDateString(),
            'effective_until' => $transaction->effective_until?->toDateString(),
            'adjustment_month' => $transaction->adjustment_month?->toDateString(),
            'amount' => $this->formatMoney($transaction->amount),
            'adjustment_amount' => $this->formatMoney($transaction->adjustment_amount),
            'description' => $transaction->description,
            'installment_frequency' => $transaction->installment_frequency?->value,
            'installments_total' => $transaction->installments_total,
            'installment_number' => $transaction->installment_number,
            'source' => $this->resolveSourceLabel($transaction),
            ...$overrides,
        ];
    }

    private function clampDate(CarbonImmutable $date, CarbonImmutable $periodStart): string
    {
        return $date->greaterThan($periodStart)
            ? $date->toDateString()
            : $periodStart->toDateString();
    }

    private function resolveSourceLabel(Transaction $transaction): string
    {
        return $transaction->account?->name ?? 'Sem origem';
    }

    private function formatMoney(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
