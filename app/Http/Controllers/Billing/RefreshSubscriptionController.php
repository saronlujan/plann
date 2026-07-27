<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\SyncStripeSubscriptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manual escape hatch for a tenant that paid but is still seeing the paywall —
 * a closed tab before the redirect, or a webhook that never arrived.
 *
 * Without this the only fix is someone editing the database by hand.
 */
class RefreshSubscriptionController extends Controller
{
    public function __invoke(Request $request, SyncStripeSubscriptions $sync): RedirectResponse
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        try {
            $subscribed = $sync->handle($tenant);
        } catch (Throwable $exception) {
            Log::error('Manual subscription refresh failed.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('error', __('billing.refresh.failed'));
        }

        return back()->with('status', $subscribed
            ? __('billing.refresh.found')
            : __('billing.refresh.not_found'));
    }
}
