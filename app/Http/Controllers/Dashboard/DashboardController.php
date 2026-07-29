<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\LabelColor;
use App\Enums\TransactionMovementType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use App\Support\Accounts\AccountStatement;
use App\Support\Onboarding\OnboardingSteps;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        TransactionProjector $projector,
        AccountStatement $statement,
        OnboardingSteps $steps,
    ): Response|RedirectResponse {
        // Nothing works without an account, so a brand-new workspace is taken
        // through the guided setup rather than shown an empty dashboard it has no
        // way to fill from here.
        if ($steps->isPending()) {
            return redirect()->route('onboarding');
        }

        $tenant = $request->user()?->tenant()->with('activeCurrencies')->first();
        abort_if($tenant === null, 403);

        $currencies = $tenant->activeCurrencies()->orderBy('code')->get();

        if ($currencies->isEmpty()) {
            return Inertia::render('Dashboard/Index', ['ready' => false]);
        }

        $categories = Category::query()->get()->keyBy('id');
        $today = CarbonImmutable::now();
        $now = $today->startOfMonth();

        $accountsByCurrency = Account::query()
            ->whereIn('currency_id', $currencies->pluck('id')->all())
            ->get()
            ->groupBy('currency_id');

        return Inertia::render('Dashboard/Index', [
            'ready' => true,
            'currencies' => $currencies
                ->map(fn (Currency $currency): array => $this->buildCurrency(
                    $currency,
                    $projector,
                    $statement,
                    $categories,
                    $accountsByCurrency->get($currency->id, collect()),
                    $now,
                    $today,
                ))
                ->all(),
        ]);
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Account>  $accounts
     * @return array<string, mixed>
     */
    private function buildCurrency(
        Currency $currency,
        TransactionProjector $projector,
        AccountStatement $statement,
        Collection $categories,
        Collection $accounts,
        CarbonImmutable $now,
        CarbonImmutable $today,
    ): array {
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
            'balance' => $this->balance($statement, $accounts, $transactions, $today),
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpenses' => $monthlyExpenses,
            'monthlyNet' => $this->money((float) $monthlyIncome - (float) $monthlyExpenses),
            'series' => $series,
            'expensesByCategory' => $this->expensesByCategory($current, $categories),
            'recent' => $this->recent($current),
        ];
    }

    /**
     * Cash on hand: the same computed balance the accounts page shows on each
     * card (opening balance + every movement booked up to today), summed across
     * the currency's accounts.
     *
     * Credit cards are excluded — they are a liability, not cash, and the
     * accounts page reports them as an invoice rather than a balance.
     *
     * @param  Collection<int, Account>  $accounts
     * @param  Collection<int, Transaction>  $transactions
     */
    private function balance(AccountStatement $statement, Collection $accounts, Collection $transactions, CarbonImmutable $today): string
    {
        $transactionsByAccount = $transactions->groupBy('account_id');

        $total = $accounts
            ->reject(fn (Account $account): bool => $account->isCreditCard())
            ->reduce(function (float $carry, Account $account) use ($statement, $transactionsByAccount, $today): float {
                $accountTransactions = $transactionsByAccount->get($account->id, collect());

                return $carry + (float) $statement->balanceAsOf($account, $accountTransactions, $today);
            }, 0.0);

        return $this->money($total);
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
                    'name' => $category->name ?? __('dashboard.uncategorized'),
                    'color' => ($category->color ?? LabelColor::Zinc)->value,
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
