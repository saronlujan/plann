<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateSettingsCurrenciesController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency_ids' => ['array'],
            'currency_ids.*' => ['integer', 'exists:currencies,id'],
        ]);

        $tenant = $request->user()?->tenant()->firstOrFail();
        $currencyIds = $validated['currency_ids'] ?? [];

        $tenant->syncCurrencyActivations($currencyIds);
        $tenant->ensureCurrencyAssets($currencyIds);

        return back()->with('success', 'Moedas atualizadas com sucesso.');
    }
}
