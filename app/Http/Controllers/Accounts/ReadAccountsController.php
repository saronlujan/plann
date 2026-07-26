<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\AccountKind;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Support\Accounts\AccountStatement;
use App\Support\Accounts\CreditCardInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadAccountsController extends Controller
{
    public function __invoke(Request $request, AccountStatement $statement, CreditCardInvoice $invoice): Response
    {
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
                    ->with(['currency', 'account'])
                    ->get();

                $base = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'kind' => $account->kind->value,
                    'currency_code' => $account->currency->code,
                ];

                if ($account->isCreditCard()) {
                    $current = $invoice->current($account, $transactions, $now);

                    return [
                        ...$base,
                        'invoice_total' => $current['total'],
                        'invoice_due_date' => $current['due_date'],
                        'available' => $current['available'],
                        'credit_limit' => $account->credit_limit === null ? null : (string) $account->credit_limit,
                    ];
                }

                $period = $statement->forPeriod($account, $transactions, $monthStart);

                return [
                    ...$base,
                    'balance' => $statement->balanceAsOf($account, $transactions, $now),
                    'monthly_income' => $period['income'],
                    'monthly_expense' => $period['expense'],
                ];
            });

        $activeCurrencyIds = $tenant->activeCurrencies()->pluck('currencies.id')->all();

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts->values()->all(),
            'currencyOptions' => Currency::query()
                ->whereIn('id', $activeCurrencyIds)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (Currency $currency): array => [
                    'value' => (string) $currency->id,
                    'label' => $currency->code.' - '.$currency->name,
                ])
                ->all(),
            'kindOptions' => AccountKind::options(),
        ]);
    }
}
