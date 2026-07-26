<?php

namespace App\Http\Controllers\Settings\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexAccountsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()?->tenant()->firstOrFail();
        $activeCurrencyIds = $tenant->activeCurrencies()->pluck('currencies.id')->all();

        return Inertia::render('Settings/Accounts', [
            'accounts' => Account::query()
                ->with('currency:id,code')
                ->orderBy('name')
                ->get(['id', 'name', 'currency_id', 'balance'])
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency_id' => $account->currency_id,
                    'currency_code' => $account->currency->code,
                    'balance' => (string) $account->balance,
                ])
                ->all(),
            'currencyOptions' => Currency::query()
                ->whereIn('id', $activeCurrencyIds)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (Currency $currency): array => [
                    'value' => (string) $currency->id,
                    'label' => $currency->code.' - '.$currency->name,
                ])
                ->all(),
        ]);
    }
}
