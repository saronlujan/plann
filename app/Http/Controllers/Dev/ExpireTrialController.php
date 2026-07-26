<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * TEMPORARY dev-only helper: force the tenant's 14-day trial to expire now so the
 * paywall/billing flow can be exercised without waiting. Registered only in the
 * local environment — remove this controller and routes/dev.php after Stripe testing.
 */
class ExpireTrialController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        // Direct assignment (not update()) because trial_ends_at is intentionally
        // guarded — it must never be mass-assignable from request input.
        $tenant->trial_ends_at = now()->subMinute();
        $tenant->save();

        return redirect()->route('billing.index');
    }
}
