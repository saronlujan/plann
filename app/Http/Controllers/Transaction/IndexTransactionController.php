<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IndexTransactionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()?->tenant()->with('activeCurrencies')->first();
        abort_if($tenant === null, 403);
        $activeCurrencyIds = $tenant->activeCurrencies()
            ->pluck('currencies.id')
            ->all();

        $period = $this->resolvePeriod($request->string('period')->toString());
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'kind' => $request->string('kind')->trim()->toString() ?: 'all',
            'order' => $request->string('order')->trim()->toString() ?: 'recent',
            'date_from' => $request->string('date_from')->trim()->toString() ?: $period->startOfMonth()->toDateString(),
            'date_to' => $request->string('date_to')->trim()->toString() ?: $period->endOfMonth()->toDateString(),
        ];

        $transactions = Transaction::query()
            ->with(['currency', 'account'])
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        $adjustedRecurringPeriods = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type === TransactionType::Recurring && $transaction->adjustment_month !== null)
            ->groupBy(fn (Transaction $transaction): string => sprintf('%s:%s', $transaction->series_uuid ?? $transaction->id, $transaction->adjustment_month->format('Y-m')));

        $entries = $this->buildEntries($transactions, $period, $adjustedRecurringPeriods)
            ->filter(function (array $entry) use ($filters): bool {
                $date = CarbonImmutable::parse($entry['date']);
                $dateFrom = CarbonImmutable::parse($filters['date_from']);
                $dateTo = CarbonImmutable::parse($filters['date_to']);

                if (! $date->betweenIncluded($dateFrom, $dateTo)) {
                    return false;
                }

                if ($filters['kind'] !== 'all' && $entry['kind'] !== $filters['kind']) {
                    return false;
                }

                if ($filters['search'] !== '') {
                    $haystack = Str::lower(implode(' ', [
                        $entry['label'],
                        $entry['source'],
                        $entry['currency_code'],
                    ]));

                    if (! Str::contains($haystack, Str::lower($filters['search']))) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        $entries = $filters['order'] === 'oldest'
            ? $entries->sortBy([['date', 'asc'], ['label', 'asc']])->values()
            : $entries->sortBy([['date', 'desc'], ['label', 'asc']])->values();

        $currencySummaries = $this->buildCurrencySummaries($entries);
        $baseFilters = [
            'search' => $filters['search'],
            'kind' => $filters['kind'],
            'order' => $filters['order'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
        ];

        return Inertia::render('Transactions/Index', [
            'period' => $period->format('Y-m'),
            'periodLabel' => $period->format('m/Y'),
            'periodDisplay' => $period->translatedFormat('F Y'),
            'periodPrevious' => $this->buildPeriodUrl($period->subMonthNoOverflow(), $baseFilters),
            'periodNext' => $this->buildPeriodUrl($period->addMonthNoOverflow(), $baseFilters),
            'filters' => $filters,
            'kindOptions' => [
                ['label' => 'All', 'value' => 'all'],
                ['label' => 'Unique', 'value' => 'unique'],
                ['label' => 'Recurring base', 'value' => 'base'],
                ['label' => 'Adjustment', 'value' => 'adjustment'],
                ['label' => 'Installment', 'value' => 'installment'],
            ],
            'currencyOptions' => Currency::query()
                ->whereIn('id', $activeCurrencyIds)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'symbol'])
                ->map(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                ])
                ->values()
                ->all(),
            'accountOptions' => Account::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('currency_id', $activeCurrencyIds)
                ->orderBy('name')
                ->get(['id', 'name', 'currency_id'])
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency_id' => $account->currency_id,
                ])
                ->values()
                ->all(),
            'currencySummaries' => $currencySummaries->values()->all(),
            'entries' => $entries->values()->all(),
            'totals' => [
                'entries' => $entries->count(),
                'unique' => $entries->where('kind', 'unique')->count(),
                'recurring' => $entries->whereIn('kind', ['base', 'adjustment'])->count(),
                'installment' => $entries->where('kind', 'installment')->count(),
                'adjustments' => $entries->where('kind', 'adjustment')->count(),
            ],
        ]);
    }

    private function resolvePeriod(string $period): CarbonImmutable
    {
        if ($period !== '') {
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $period.'-01')->startOfMonth();
            } catch (\Throwable) {
            }
        }

        return now()->toImmutable()->startOfMonth();
    }

    private function buildPeriodUrl(CarbonImmutable $period, array $filters): string
    {
        return route('transactions.index', array_merge($filters, [
            'period' => $period->format('Y-m'),
            'date_from' => $period->startOfMonth()->toDateString(),
            'date_to' => $period->endOfMonth()->toDateString(),
        ]));
    }

    private function buildEntries(Collection $transactions, CarbonImmutable $period, Collection $adjustedRecurringPeriods): Collection
    {
        $periodStart = $period->startOfMonth();
        $periodEnd = $period->endOfMonth();
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

    private function mapUniqueEntry(Transaction $transaction, CarbonImmutable $periodStart): array
    {
        $date = $transaction->effective_date->greaterThan($periodStart)
            ? $transaction->effective_date->toDateString()
            : $periodStart->toDateString();

        return [
            'id' => sprintf('transaction-%s', $transaction->id),
            'transaction_id' => $transaction->id,
            'date' => $date,
            'kind' => 'unique',
            'type' => TransactionType::Unique->value,
            'schedule_type' => TransactionType::Unique->value,
            'movement_type' => $transaction->movement_type?->value ?? TransactionMovementType::Expense->value,
            'label' => $transaction->description,
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
        ];
    }

    private function mapRecurringEntry(Transaction $transaction, CarbonImmutable $periodStart): array
    {
        $date = $transaction->effective_date->greaterThan($periodStart)
            ? $transaction->effective_date->toDateString()
            : $periodStart->toDateString();

        return [
            'id' => sprintf('transaction-%s', $transaction->id),
            'transaction_id' => $transaction->id,
            'date' => $date,
            'kind' => 'base',
            'type' => TransactionType::Recurring->value,
            'schedule_type' => TransactionType::Recurring->value,
            'movement_type' => $transaction->movement_type?->value ?? TransactionMovementType::Expense->value,
            'label' => sprintf('%s - recorrência', $transaction->description),
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
        ];
    }

    private function mapAdjustmentEntry(Transaction $transaction, CarbonImmutable $period): array
    {
        $date = $transaction->adjustment_month?->toDateString() ?? $period->toDateString();

        return [
            'id' => sprintf('transaction-%s-adjustment', $transaction->id),
            'transaction_id' => $transaction->id,
            'date' => $date,
            'kind' => 'adjustment',
            'type' => TransactionType::Recurring->value,
            'schedule_type' => TransactionType::Recurring->value,
            'movement_type' => $transaction->movement_type?->value ?? TransactionMovementType::Expense->value,
            'label' => sprintf('%s - ajuste %s', $transaction->description, $period->format('m/Y')),
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionMetadata(Transaction $transaction): array
    {
        return [
            'transaction_id' => $transaction->id,
            'movement_type' => $transaction->movement_type?->value ?? TransactionMovementType::Expense->value,
            'schedule_type' => $transaction->type->value,
            'currency_id' => $transaction->currency_id,
            'account_id' => $transaction->account_id,
            'effective_date' => $transaction->effective_date->toDateString(),
            'paid_at' => $transaction->paid_at?->toDateString(),
            'adjustment_month' => $transaction->adjustment_month?->toDateString(),
            'description' => $transaction->description,
            'adjustment_amount' => $this->formatMoney($transaction->adjustment_amount),
            'installment_frequency' => $transaction->installment_frequency?->value,
            'installments_total' => $transaction->installments_total,
            'installment_number' => $transaction->installment_number,
        ];
    }

    private function buildCurrencySummaries(Collection $entries): Collection
    {
        return Currency::query()
            ->orderBy('code')
            ->get()
            ->map(function (Currency $currency) use ($entries): array {
                $currencyEntries = $entries->where('currency_code', $currency->code);

                return [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'entries' => $currencyEntries->count(),
                    'total' => $this->formatMoney($currencyEntries->sum(fn (array $entry): float => (float) $entry['amount'])),
                ];
            });
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
