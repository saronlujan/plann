<?php

namespace App\Http\Controllers\Currencies;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeleteCurrencyController extends Controller
{
    public function __invoke(Request $request, Currency $currency): RedirectResponse
    {
        // Managing currencies only means anything on a plan that can keep more
        // than one active; otherwise a workspace could create one it can never
        // activate. Resolved with a fresh query: the authenticated user's cached
        // tenant relation can still hold the plan from before a downgrade.
        $tenant = $request->user()->tenant()->first();

        abort_unless($tenant?->hasFeature(PlanFeature::MultiCurrency) === true, 403);

        // The shared catalogue is not a workspace's to delete.
        abort_if($currency->isGlobal(), 403);
        abort_unless($currency->tenant_id === $request->user()?->tenant_id, 404);

        // Entries are the only thing that makes a currency undeletable: they carry
        // amounts, and there is no honest way to reinterpret those.
        if (Transaction::query()->where('currency_id', $currency->id)->exists()) {
            return back()->withErrors(['currency' => __('currencies.errors.in_use')]);
        }

        DB::transaction(function () use ($currency): void {
            // accounts.currency_id is RESTRICT, so the accounts have to go first.
            // They are provably empty — the check above found no entries — and an
            // account denominated in a currency the workspace no longer keeps has
            // nothing left to hold. Deleted as models so the deleting hooks run.
            Account::query()->where('currency_id', $currency->id)->get()->each->delete();

            $currency->tenants()->detach();
            $currency->delete();
        });

        return back()->with('status', __('currencies.deleted'));
    }
}
