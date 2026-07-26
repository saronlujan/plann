<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\AccountKind;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\Accounts\AccountStatement;
use App\Support\Accounts\CreditCardInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ShowAccountController extends Controller
{
    public function __invoke(Request $request, Account $account, AccountStatement $statement, CreditCardInvoice $invoice): Response
    {
        $transactions = Transaction::query()
            ->where('account_id', $account->id)
            ->with(['currency', 'account'])
            ->get();

        $categories = Category::query()->get()->keyBy('id');

        if ($account->isCreditCard()) {
            return $this->renderCard($account, $transactions, $categories, $invoice);
        }

        return $this->renderAccount($request, $account, $transactions, $categories, $statement);
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Category>  $categories
     */
    private function renderAccount(Request $request, Account $account, Collection $transactions, Collection $categories, AccountStatement $statement): Response
    {
        $period = $this->resolvePeriod($request->string('period')->toString());
        $data = $statement->forPeriod($account, $transactions, $period);

        $entries = collect($data['entries'])
            ->map(fn (array $entry): array => [
                ...$entry,
                'category' => $this->categoryName($categories, $entry['category_id']),
                'color' => $this->categoryColor($categories, $entry['category_id']),
            ])
            ->all();

        return Inertia::render('Accounts/Show', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'kind' => $account->kind->value,
                'currency_code' => $account->currency->code,
            ],
            'period' => $period->format('Y-m'),
            'opening' => $data['opening'],
            'closing' => $data['closing'],
            'income' => $data['income'],
            'expense' => $data['expense'],
            'entries' => $entries,
        ]);
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Category>  $categories
     */
    private function renderCard(Account $account, Collection $transactions, Collection $categories, CreditCardInvoice $invoice): Response
    {
        $current = $invoice->current($account, $transactions, CarbonImmutable::now());

        $entries = collect($current['entries'])
            ->map(fn (array $entry): array => [
                ...$entry,
                'category' => $this->categoryName($categories, $entry['category_id']),
                'color' => $this->categoryColor($categories, $entry['category_id']),
            ])
            ->all();

        $payAccounts = Account::query()
            ->where('tenant_id', $account->tenant_id)
            ->where('kind', '!=', AccountKind::CreditCard->value)
            ->where('currency_id', $account->currency_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Account $bank): array => [
                'value' => (string) $bank->id,
                'label' => $bank->name,
            ])
            ->all();

        return Inertia::render('Accounts/ShowCard', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'currency_code' => $account->currency->code,
                'credit_limit' => $account->credit_limit === null ? null : (string) $account->credit_limit,
                'closing_day' => $account->closing_day,
                'due_day' => $account->due_day,
            ],
            'invoice' => [
                'cycle_start' => $current['cycle_start'],
                'cycle_end' => $current['cycle_end'],
                'due_date' => $current['due_date'],
                'total' => $current['total'],
                'outstanding' => $current['outstanding'],
                'available' => $current['available'],
            ],
            'entries' => $entries,
            'payAccounts' => $payAccounts,
        ]);
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function categoryName(Collection $categories, ?int $categoryId): ?string
    {
        return $categoryId === null ? null : $categories->get($categoryId)?->name;
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function categoryColor(Collection $categories, ?int $categoryId): ?string
    {
        return $categoryId === null ? null : $categories->get($categoryId)?->color->value;
    }

    private function resolvePeriod(string $period): CarbonImmutable
    {
        if ($period !== '') {
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $period.'-01')->startOfMonth();
            } catch (\Throwable) {
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }
}
