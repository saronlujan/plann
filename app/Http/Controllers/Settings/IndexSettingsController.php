<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexSettingsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()?->tenant()->with('activeCurrencies')->first();
        abort_if($tenant === null, 403);

        $activeCurrencyIds = $tenant->activeCurrencies()
            ->pluck('currencies.id')
            ->all();

        return Inertia::render('Settings/Index', [
            'currencies' => Currency::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'symbol'])
                ->map(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'is_active' => in_array($currency->id, $activeCurrencyIds, true),
                ])
                ->values()
                ->all(),
            'locale' => $tenant->locale ?: config('app.locale'),
            'localeOptions' => [
                ['value' => 'pt', 'label' => 'Português'],
                ['value' => 'es', 'label' => 'Español'],
                ['value' => 'en', 'label' => 'English'],
            ],
        ]);
    }
}
