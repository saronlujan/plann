<?php

namespace App\Http\Controllers\Currencies;

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
                ->get(['id', 'code', 'name', 'symbol'])
                ->map(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'active' => in_array($currency->id, $activeCurrencyIds, true),
                ])
                ->all(),
        ]);
    }
}
