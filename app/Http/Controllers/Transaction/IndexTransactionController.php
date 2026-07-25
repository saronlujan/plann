<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IndexTransactionController extends Controller
{
    public function __invoke(Request $request, TransactionProjector $projector): Response
    {
        $tenant = $request->user()?->tenant()->with('activeCurrencies')->first();
        abort_if($tenant === null, 403);

        $activeCurrencies = $tenant->activeCurrencies()
            ->orderBy('code')
            ->get(['currencies.id', 'currencies.code', 'currencies.name', 'currencies.symbol']);
        $activeCurrencyIds = $activeCurrencies->pluck('id')->all();

        $period = $this->resolvePeriod($request->string('period')->toString());
        $periodStart = $period->startOfMonth();
        $periodEnd = $period->endOfMonth();

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'kind' => $request->string('kind')->trim()->toString() ?: 'all',
            'order' => $request->string('order')->trim()->toString() ?: 'recent',
            'date_from' => $request->string('date_from')->trim()->toString() ?: $periodStart->toDateString(),
            'date_to' => $request->string('date_to')->trim()->toString() ?: $periodEnd->toDateString(),
        ];

        $transactions = Transaction::query()
            ->with(['currency', 'account'])
            ->where('effective_date', '<=', $periodEnd->toDateString())
            ->where(function (Builder $query) use ($periodStart): void {
                $query->where('type', '!=', 'unique')
                    ->orWhere('effective_date', '>=', $periodStart->toDateString());
            })
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        $entries = $projector->entriesForPeriod($transactions, $period)
            ->filter(fn (array $entry): bool => $this->matchesFilters($entry, $filters))
            ->values();

        $entries = $filters['order'] === 'oldest'
            ? $entries->sortBy([['date', 'asc'], ['label', 'asc']])->values()
            : $entries->sortBy([['date', 'desc'], ['label', 'asc']])->values();

        $currencySummaries = $projector->currencySummaries($activeCurrencies, $entries);
        $baseFilters = $filters;

        return Inertia::render('Transactions/Index', [
            'period' => $period->format('Y-m'),
            'periodLabel' => $period->format('m/Y'),
            'periodDisplay' => $period->translatedFormat('F Y'),
            'periodPrevious' => $this->buildPeriodUrl($period->subMonthNoOverflow(), $baseFilters),
            'periodNext' => $this->buildPeriodUrl($period->addMonthNoOverflow(), $baseFilters),
            'filters' => $filters,
            'kindOptions' => [
                ['label' => 'Todas', 'value' => 'all'],
                ['label' => 'Única', 'value' => 'unique'],
                ['label' => 'Recorrência', 'value' => 'base'],
                ['label' => 'Ajuste', 'value' => 'adjustment'],
                ['label' => 'Parcela', 'value' => 'installment'],
            ],
            'movementTypeOptions' => array_map(
                fn (TransactionMovementType $type): array => ['value' => $type->value, 'label' => $type->label()],
                TransactionMovementType::cases(),
            ),
            'scheduleTypeOptions' => array_map(
                fn (TransactionType $type): array => ['value' => $type->value, 'label' => $type->label()],
                TransactionType::cases(),
            ),
            'frequencyOptions' => array_map(
                fn (TransactionInstallmentFrequency $frequency): array => ['value' => $frequency->value, 'label' => $frequency->label()],
                TransactionInstallmentFrequency::cases(),
            ),
            'currencyOptions' => $activeCurrencies
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

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, string>  $filters
     */
    private function matchesFilters(array $entry, array $filters): bool
    {
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

    /**
     * @param  array<string, string>  $filters
     */
    private function buildPeriodUrl(CarbonImmutable $period, array $filters): string
    {
        return route('transactions.index', array_merge($filters, [
            'period' => $period->format('Y-m'),
            'date_from' => $period->startOfMonth()->toDateString(),
            'date_to' => $period->endOfMonth()->toDateString(),
        ]));
    }
}
