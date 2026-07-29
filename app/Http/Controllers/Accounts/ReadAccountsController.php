<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\AccountKind;
use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Accounts\AccountStatement;
use App\Support\Accounts\CreditCardInvoice;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReadAccountsController extends Controller
{
    public function __invoke(
        Request $request,
        AccountStatement $statement,
        CreditCardInvoice $invoice,
    ): Response {
        $tenant = $request->user()?->tenant()->firstOrFail();

        $now = CarbonImmutable::now();
        $monthStart = $now->startOfMonth();

        $accounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->with('currency')
            ->orderBy('name')
            ->get()
            ->map(function (Account $account) use ($statement, $invoice, $now, $monthStart): array {
                $transactions = Transaction::query()
                    ->where('account_id', $account->id)
                    ->with(TransactionProjector::RELATIONS)
                    ->get();

                // Fields the edit modal needs, present on every account. `balance`
                // is the stored opening balance the modal edits — distinct from the
                // computed current balance shown on the card.
                $base = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'kind' => $account->kind->value,
                    'currency_id' => $account->currency_id,
                    'currency_code' => $account->currency->code,
                    'credit_limit' => $account->credit_limit === null ? null : (string) $account->credit_limit,
                    'closing_day' => $account->closing_day,
                    'due_day' => $account->due_day,
                    // Once money has moved through it the currency is settled:
                    // every entry stores the currency it was recorded in.
                    'has_transactions' => $transactions->isNotEmpty(),
                ];

                $spark = $this->spark($statement, $account, $transactions, $now);

                if ($account->isCreditCard()) {
                    $current = $invoice->current($account, $transactions, $now);

                    return [
                        ...$base,
                        'invoice_total' => $current['total'],
                        'invoice_due_date' => $current['due_date'],
                        'available' => $current['available'],
                        'spark' => $spark,
                    ];
                }

                $period = $statement->forPeriod($account, $transactions, $monthStart);

                return [
                    ...$base,
                    'current_balance' => $statement->balanceAsOf($account, $transactions, $now),
                    'monthly_income' => $period['income'],
                    'monthly_expense' => $period['expense'],
                    'spark' => $spark,
                ];
            });

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts->values()->all(),
            // On a single-currency plan the list is the one currency the
            // workspace may hold, so the form hides the field entirely. Pro gets
            // the catalogue: opening an account is how a new currency starts
            // being used.
            'currencyOptions' => $this->currencyOptionsQuery($tenant)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'symbol'])
                ->map(fn (Currency $currency): array => [
                    'value' => (string) $currency->id,
                    'label' => $currency->code.' - '.$currency->name,
                    // Prefix and decimal places for the money fields in the modal.
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                ])
                ->all(),
            // The currency chosen at signup, not whichever sorts first: on Pro the
            // list is the whole catalogue and starts at ARS.
            'defaultCurrencyId' => (string) ($tenant->currency_id ?? ''),
            'kindOptions' => AccountKind::options(),
        ]);
    }

    /**
     * Which currencies an account may be opened in.
     *
     * Before the first account there is nothing in use, so the currency declared
     * at signup stands in — otherwise a Basic workspace would be offered the whole
     * catalogue and could lock itself into a currency it never chose.
     *
     * @return Builder<Currency>
     */
    private function currencyOptionsQuery(Tenant $tenant): Builder
    {
        if ($tenant->hasFeature(PlanFeature::MultiCurrency)) {
            return Currency::query();
        }

        $inUse = $tenant->activeCurrencies()->pluck('currencies.id')->all();

        if ($inUse === []) {
            $inUse = array_filter([$tenant->currency_id]);
        }

        return Currency::query()->whereIn('id', $inUse);
    }

    /**
     * End-of-month computed balance for the last 6 months (oldest → newest),
     * used to draw the sparkline on each account card.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, float>
     */
    private function spark(AccountStatement $statement, Account $account, Collection $transactions, CarbonImmutable $now): array
    {
        return collect(range(5, 0))
            ->map(function (int $ago) use ($statement, $account, $transactions, $now): float {
                $asOf = $ago === 0 ? $now : $now->subMonths($ago)->endOfMonth();

                return (float) $statement->balanceAsOf($account, $transactions, $asOf);
            })
            ->all();
    }
}
