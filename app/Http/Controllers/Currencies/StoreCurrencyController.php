<?php

namespace App\Http\Controllers\Currencies;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\StoreCurrencyRequest;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;

class StoreCurrencyController extends Controller
{
    /**
     * Add a currency the shared catalogue does not carry. It belongs to this
     * workspace alone and is invisible to everyone else.
     */
    public function __invoke(StoreCurrencyRequest $request): RedirectResponse
    {
        // Managing currencies only means anything on a plan that can keep more
        // than one active; otherwise a workspace could create one it can never
        // activate. Resolved with a fresh query: the authenticated user's cached
        // tenant relation can still hold the plan from before a downgrade.
        $tenant = $request->user()->tenant()->first();

        abort_unless($tenant?->hasFeature(PlanFeature::MultiCurrency) === true, 403);

        $validated = $request->validated();

        Currency::query()->create([
            'tenant_id' => $request->user()?->tenant_id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'symbol' => $validated['symbol'],
        ]);

        return back()->with('status', __('currencies.created'));
    }
}
