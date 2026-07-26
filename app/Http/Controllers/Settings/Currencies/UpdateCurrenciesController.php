<?php

namespace App\Http\Controllers\Settings\Currencies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\UpdateCurrenciesRequest;
use Illuminate\Http\RedirectResponse;

class UpdateCurrenciesController extends Controller
{
    public function __invoke(UpdateCurrenciesRequest $request): RedirectResponse
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        $tenant->syncCurrencyActivations($request->input('currency_ids', []));

        return back();
    }
}
