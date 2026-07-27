<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tag;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class IndexTransactionController extends Controller
{
    public function __invoke(Request $request, TransactionProjector $projector): Response
    {
        $tenant = $request->user()?->tenant()->with('activeCurrencies')->first();
        abort_if($tenant === null, 403);

        // Only currencies with an account: the rest cannot hold a transaction.
        $activeCurrencies = $tenant->activeCurrencies()
            ->usable()
            ->orderBy('code')
            ->get(['currencies.id', 'currencies.code', 'currencies.name', 'currencies.symbol']);
        $activeCurrencyIds = $activeCurrencies->pluck('id')->all();

        $period = $this->resolvePeriod($request->string('period')->toString());
        $periodStart = $period->startOfMonth();
        $periodEnd = $period->endOfMonth();

        $transactions = Transaction::query()
            ->with(['currency', 'account', 'tags:id'])
            ->where('effective_date', '<=', $periodEnd->toDateString())
            ->where(function (Builder $query) use ($periodStart): void {
                $query->where('type', '!=', 'unique')
                    ->orWhere('effective_date', '>=', $periodStart->toDateString());
            })
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        $transferLegs = $this->transferLegs($transactions);

        $entries = $projector->entriesForPeriod($transactions, $period)
            ->map(fn (array $entry): array => $this->withTransferLegs($entry, $transferLegs))
            ->sortBy([['date', 'desc'], ['label', 'asc']])
            ->values();

        $summaries = $projector->summaries($activeCurrencies, $entries);

        return Inertia::render('Transactions/Index', [
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
            'categoryOptions' => Category::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color->value,
                ])
                ->values()
                ->all(),
            'tagOptions' => Tag::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'color'])
                ->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color->value,
                ])
                ->values()
                ->all(),
            'entries' => $entries->all(),
            'summaries' => $summaries->all(),
        ]);
    }

    /**
     * Attach the origin/destination account names to a projected entry.
     *
     * @param  array<string, mixed>  $entry
     * @param  array<int, array{from: ?string, to: ?string}>  $transferLegs
     * @return array<string, mixed>
     */
    private function withTransferLegs(array $entry, array $transferLegs): array
    {
        $legs = $transferLegs[$entry['transaction_id']] ?? null;

        $entry['transfer_from'] = $legs['from'] ?? null;
        $entry['transfer_to'] = $legs['to'] ?? null;

        return $entry;
    }

    /**
     * Map each transfer leg's transaction id to the origin/destination account
     * names, resolved from its sibling leg (same series_uuid).
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, array{from: ?string, to: ?string}>
     */
    private function transferLegs(Collection $transactions): array
    {
        $bySeries = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->is_transfer && $transaction->series_uuid !== null)
            ->groupBy('series_uuid')
            ->map(fn ($legs): array => [
                'from' => $legs->first(fn (Transaction $leg): bool => $leg->movement_type === TransactionMovementType::Expense)?->account?->name,
                'to' => $legs->first(fn (Transaction $leg): bool => $leg->movement_type === TransactionMovementType::Income)?->account?->name,
            ]);

        $legs = [];

        foreach ($transactions as $transaction) {
            if ($transaction->is_transfer && isset($bySeries[$transaction->series_uuid])) {
                $legs[$transaction->id] = $bySeries[$transaction->series_uuid];
            }
        }

        return $legs;
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
}
