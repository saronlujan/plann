<?php

namespace App\Http\Controllers\Services;

use App\Enums\LabelColor;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexServicesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()?->tenant;

        return Inertia::render('Services/Index', [
            'services' => Service::query()
                ->orderBy('name')
                ->get(['id', 'name', 'default_price', 'currency_id', 'color'])
                ->map(fn (Service $service): array => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'default_price' => $service->default_price,
                    'currency_id' => $service->currency_id,
                    'color' => $service->color,
                ])
                ->all(),
            // Pricing is offered only in currencies the workspace already keeps an
            // account in, so the list mirrors what the transaction form can accept.
            'currencyOptions' => $tenant instanceof Tenant
                ? $tenant->activeCurrencies()
                    ->orderBy('code')
                    ->get()
                    ->map(fn (Currency $currency): array => [
                        'value' => (string) $currency->id,
                        'label' => $currency->code.' - '.$currency->name,
                        // The field formats as you type: the symbol prefixes it and
                        // the code decides whether it takes cents at all.
                        'symbol' => $currency->symbol,
                        'code' => $currency->code,
                    ])
                    ->all()
                : [],
            'colorOptions' => LabelColor::options(),
        ]);
    }
}
