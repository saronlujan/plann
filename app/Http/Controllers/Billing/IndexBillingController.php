<?php

namespace App\Http\Controllers\Billing;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\Billing\BillingService;
use App\Services\Billing\SyncStripeSubscriptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class IndexBillingController extends Controller
{
    public function __invoke(
        Request $request,
        BillingService $billing,
        SyncStripeSubscriptions $sync,
    ): Response {
        $tenant = $request->user()?->tenant()->firstOrFail();

        if ($request->string('checkout')->toString() === 'success') {
            $this->syncAfterCheckout($sync, $tenant);
        }

        return Inertia::render('Billing/Index', [
            'plans' => $billing->plans()
                ->map(fn (Plan $plan): array => [
                    'slug' => $plan->slug->value,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'features' => array_map(
                        fn (PlanFeature $feature): string => $feature->label(),
                        $plan->slug->features(),
                    ),
                    'monthly_price_cents' => $plan->monthly_price_cents,
                    'annual_price_cents' => $plan->annualPriceCents(),
                    'available' => $plan->stripe_price_id !== null,
                ])
                ->all(),
            'status' => $billing->status($tenant->fresh() ?? $tenant),
            'invoices' => $billing->invoices($tenant),
        ]);
    }

    /**
     * Stripe sends the browser back the moment payment succeeds, well before the
     * webhook lands. Pulling the subscription here is what actually unlocks the
     * app for someone who just paid.
     *
     * A failure must not break the page: the webhook is still coming, and the
     * user can retry from the billing screen.
     */
    private function syncAfterCheckout(SyncStripeSubscriptions $sync, Tenant $tenant): void
    {
        try {
            $sync->handle($tenant);
        } catch (Throwable $exception) {
            Log::error('Could not sync the subscription after checkout.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
