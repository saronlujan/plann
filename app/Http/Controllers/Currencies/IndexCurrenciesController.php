<?php

namespace App\Http\Controllers\Currencies;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexCurrenciesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()?->tenant()->firstOrFail();
        $activeCurrencyIds = $tenant->activeCurrencies()->pluck('currencies.id')->all();

        return Inertia::render('Currencies/Index', [
            'currencies' => Currency::query()
                ->orderBy('code')
                // tenant_id is what tells a workspace's own currency from the shared
                // catalogue; leaving it out makes every row look global.
                // Accounts are tenant-scoped globally, so this counts only this
                // workspace's — it drives the "activate an account too" warning.
                ->withCount('accounts')
                ->get(['id', 'tenant_id', 'code', 'name', 'symbol'])
                ->map(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'active' => in_array($currency->id, $activeCurrencyIds, true),
                    'custom' => ! $currency->isGlobal(),
                    'has_accounts' => $currency->accounts_count > 0,
                ])
                ->all(),
            // Drives the upgrade prompt: on Basic the list is read-only past the
            // single currency the plan allows.
            'canUseMultiCurrency' => $tenant->hasFeature(PlanFeature::MultiCurrency),
        ]);
    }
}
