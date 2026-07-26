<?php

namespace App\Http\Controllers\Budget;

use App\Enums\TransactionMovementType;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class IndexBudgetsController extends Controller
{
    public function __invoke(Request $request, TransactionProjector $projector): Response
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        $budgets = Budget::query()->with(['category', 'currency'])->get();
        $spent = $this->spentByCurrencyAndCategory($budgets, $projector);

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets
                ->sortBy(fn (Budget $budget): string => $budget->category->name)
                ->map(fn (Budget $budget): array => [
                    'id' => $budget->id,
                    'category_id' => $budget->category_id,
                    'category' => $budget->category->name,
                    'color' => $budget->category->color->value,
                    'currency_id' => $budget->currency_id,
                    'currency_code' => $budget->currency->code,
                    'amount' => $budget->amount,
                    'spent' => $spent[$budget->currency_id][$budget->category_id] ?? '0.00',
                ])
                ->values()
                ->all(),
            'categoryOptions' => Category::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('type', ['expense', 'both'])
                ->orderBy('name')
                ->get(['id', 'name', 'color'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color->value,
                ])
                ->all(),
            'currencyOptions' => $tenant->activeCurrencies()
                ->orderBy('code')
                ->get(['currencies.id', 'currencies.code', 'currencies.name'])
                ->map(fn ($currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                ])
                ->all(),
        ]);
    }

    /**
     * Current-month expenses grouped as [currency_id][category_id] => amount.
     *
     * @param  Collection<int, Budget>  $budgets
     * @return array<int, array<int, string>>
     */
    private function spentByCurrencyAndCategory(Collection $budgets, TransactionProjector $projector): array
    {
        $now = CarbonImmutable::now()->startOfMonth();
        $result = [];

        foreach ($budgets->pluck('currency_id')->unique() as $currencyId) {
            $transactions = Transaction::query()
                ->where('currency_id', $currencyId)
                ->with(['currency', 'account'])
                ->get();

            $result[$currencyId] = $projector->entriesForPeriod($transactions, $now)
                ->where('movement_type', TransactionMovementType::Expense->value)
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->map(fn (Collection $group): string => number_format(
                    $group->reduce(fn (float $carry, array $entry): float => $carry + (float) $entry['amount'], 0.0),
                    2,
                    '.',
                    ''
                ))
                ->all();
        }

        return $result;
    }
}
