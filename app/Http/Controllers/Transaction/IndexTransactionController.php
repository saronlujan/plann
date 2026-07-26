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

        $entries = $projector->entriesForPeriod($transactions, $period)
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
