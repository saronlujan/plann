<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\LabelColor;
use App\Enums\TransactionMovementType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TransactionProjector $projector): Response
    {
        $tenant = $request->user()?->tenant()->with('activeCurrencies')->first();
        abort_if($tenant === null, 403);

        $currencies = $tenant->activeCurrencies()->orderBy('code')->get();

        if ($currencies->isEmpty()) {
            return Inertia::render('Dashboard/Index', ['ready' => false]);
        }

        $categories = Category::query()->get()->keyBy('id');
        $now = CarbonImmutable::now()->startOfMonth();

        return Inertia::render('Dashboard/Index', [
            'ready' => true,
            'currencies' => $currencies
                ->map(fn (Currency $currency): array => $this->buildCurrency($currency, $projector, $categories, $now))
                ->all(),
        ]);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array<string, mixed>
     */
    private function buildCurrency(Currency $currency, TransactionProjector $projector, Collection $categories, CarbonImmutable $now): array
    {
        $transactions = Transaction::query()
            ->where('currency_id', $currency->id)
            ->with(['currency', 'account'])
            ->get();

        $series = collect(range(5, 0))
            ->map(function (int $ago) use ($now, $projector, $transactions): array {
                $period = $now->subMonths($ago);
                $entries = $projector->entriesForPeriod($transactions, $period);

                return [
                    'label' => $period->isoFormat('MMM'),
                    'month' => $period->format('Y-m'),
                    'income' => $this->sum($entries, TransactionMovementType::Income),
                    'expense' => $this->sum($entries, TransactionMovementType::Expense),
                ];
            })
            ->values()
            ->all();

        $current = $projector->entriesForPeriod($transactions, $now);
        $monthlyIncome = $this->sum($current, TransactionMovementType::Income);
        $monthlyExpenses = $this->sum($current, TransactionMovementType::Expense);

        return [
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'balance' => $this->money(Account::query()->where('currency_id', $currency->id)->sum('balance')),
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpenses' => $monthlyExpenses,
            'monthlyNet' => $this->money((float) $monthlyIncome - (float) $monthlyExpenses),
            'series' => $series,
            'expensesByCategory' => $this->expensesByCategory($current, $categories),
            'recent' => $this->recent($current),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     */
    private function sum(Collection $entries, TransactionMovementType $movement): string
    {
        return $this->money(
            $entries
                ->where('movement_type', $movement->value)
                ->reject(fn (array $entry): bool => $entry['is_transfer'] === true)
                ->reduce(fn (float $carry, array $entry): float => $carry + (float) $entry['amount'], 0.0)
        );
    }

    /**
     * Expenses of the current month grouped by category, sorted high → low.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @param  Collection<int, Category>  $categories
     * @return array<int, array{name: string, color: string, value: string}>
     */
    private function expensesByCategory(Collection $entries, Collection $categories): array
    {
        return $entries
            ->where('movement_type', TransactionMovementType::Expense->value)
            ->reject(fn (array $entry): bool => $entry['is_transfer'] === true)
            ->groupBy(fn (array $entry): string => (string) ($entry['category_id'] ?? 'none'))
            ->map(function (Collection $group, string $key) use ($categories): array {
                $category = $key === 'none' ? null : $categories->get((int) $key);

                return [
                    'name' => $category?->name ?? __('dashboard.uncategorized'),
                    'color' => ($category?->color ?? LabelColor::Zinc)->value,
                    'value' => $this->money($group->reduce(fn (float $carry, array $entry): float => $carry + (float) $entry['amount'], 0.0)),
                ];
            })
            ->filter(fn (array $slice): bool => (float) $slice['value'] > 0)
            ->sortByDesc(fn (array $slice): float => (float) $slice['value'])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function recent(Collection $entries): array
    {
        return $entries
            ->sortByDesc('date')
            ->take(8)
            ->map(fn (array $entry): array => [
                'id' => $entry['id'],
                'description' => $entry['description'],
                'movement_type' => $entry['movement_type'],
                'amount' => $this->money((float) $entry['amount']),
                'date' => $entry['date'],
                'paid' => $entry['paid_at'] !== null,
                'account' => $entry['source'],
            ])
            ->values()
            ->all();
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
