<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Invoice;
use Throwable;

class BillingService
{
    /**
     * @return Collection<int, Plan>
     */
    public function plans(): Collection
    {
        return Plan::query()->active()->orderBy('sort_order')->get();
    }

    /**
     * Current subscription/trial status for the tenant.
     *
     * @return array<string, mixed>
     */
    public function status(Tenant $tenant): array
    {
        $subscription = $tenant->subscription('default');

        return [
            'plan_slug' => $tenant->plan_slug->value,
            'subscribed' => $tenant->subscribed(),
            'on_trial' => $tenant->onTrial(),
            'on_grace_period' => (bool) $subscription?->onGracePeriod(),
            'trial_ends_at' => $tenant->trial_ends_at?->toDateString(),
            'trial_days_left' => $tenant->onTrial()
                ? max(0, (int) ceil(now()->diffInDays($tenant->trial_ends_at, false)))
                : 0,
            'current_price_id' => $subscription?->stripe_price,
        ];
    }

    /**
     * Recent invoices as flat arrays (empty when the tenant has no Stripe customer yet).
     *
     * Never throws. This is the page a locked-out tenant is redirected to, so a
     * Stripe outage or a stale customer id must degrade to "no invoices", not to
     * a 500 that leaves them with nowhere to go.
     *
     * @return array<int, array<string, mixed>>
     */
    public function invoices(Tenant $tenant): array
    {
        if (! $tenant->hasStripeId()) {
            return [];
        }

        try {
            return $tenant->invoices()
                ->map(fn (Invoice $invoice): array => [
                    'id' => $invoice->asStripeInvoice()->id,
                    'date' => $invoice->date()->toDateString(),
                    'total' => $invoice->total(),
                    'status' => $invoice->asStripeInvoice()->status,
                ])
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Could not load Stripe invoices.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
