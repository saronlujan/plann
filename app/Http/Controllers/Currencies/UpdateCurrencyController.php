<?php

namespace App\Http\Controllers\Currencies;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;

class UpdateCurrencyController extends Controller
{
    public function __invoke(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        // Managing currencies only means anything on a plan that can keep more
        // than one active; otherwise a workspace could create one it can never
        // activate. Resolved with a fresh query: the authenticated user's cached
        // tenant relation can still hold the plan from before a downgrade.
        $tenant = $request->user()->tenant()->first();

        abort_unless($tenant?->hasFeature(PlanFeature::MultiCurrency) === true, 403);

        // The shared catalogue is not a workspace's to rewrite.
        abort_if($currency->isGlobal(), 403);
        abort_unless($currency->tenant_id === $request->user()?->tenant_id, 404);

        $currency->update($request->validated());

        return back()->with('status', __('currencies.updated'));
    }
}
