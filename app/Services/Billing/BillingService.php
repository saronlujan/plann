<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Collection;

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
            'plan_slug' => $tenant->plan_slug?->value,
            'subscribed' => $tenant->subscribed(),
            'on_trial' => $tenant->onTrial(),
            'on_grace_period' => (bool) $subscription?->onGracePeriod(),
            'trial_ends_at' => $tenant->trial_ends_at?->toDateString(),
            'trial_days_left' => $tenant->onTrial()
                ? max(0, (int) ceil(now()->floatDiffInDays($tenant->trial_ends_at, false)))
                : 0,
            'current_price_id' => $subscription?->stripe_price,
        ];
    }

    /**
     * Recent invoices as flat arrays (empty when the tenant has no Stripe customer yet).
     *
     * @return array<int, array<string, mixed>>
     */
    public function invoices(Tenant $tenant): array
    {
        if (! $tenant->hasStripeId()) {
            return [];
        }

        return $tenant->invoices()
            ->map(fn ($invoice): array => [
                'id' => $invoice->id,
                'date' => $invoice->date()->toDateString(),
                'total' => $invoice->total(),
                'status' => $invoice->status,
            ])
            ->all();
    }

    public function canAddUser(Tenant $tenant): bool
    {
        return $tenant->canAddUser();
    }
}
