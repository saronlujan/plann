<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

/**
 * Mirrors a tenant's Stripe subscriptions into the local tables.
 *
 * Cashier only writes those rows when its webhook fires. Stripe, meanwhile,
 * redirects the browser back the instant payment succeeds — so a user who has
 * just paid lands on the paywall until the webhook happens to arrive, and stays
 * there forever if the endpoint is misconfigured or unreachable.
 *
 * Pulling the state straight from Stripe on return from Checkout makes access
 * immediate and removes the webhook as a single point of failure. The webhook
 * stays on as the durable channel for renewals and cancellations.
 */
class SyncStripeSubscriptions
{
    /**
     * Fetch the tenant's subscriptions from Stripe and mirror them locally.
     *
     * @return bool whether the tenant ended up with a usable subscription
     */
    public function handle(Tenant $tenant): bool
    {
        if (! $tenant->hasStripeId()) {
            return false;
        }

        $subscriptions = Cashier::stripe()->subscriptions->all([
            'customer' => $tenant->stripeId(),
            'status' => 'all',
            'expand' => ['data.items'],
            'limit' => 10,
        ]);

        foreach ($subscriptions->data as $subscription) {
            $this->mirror($tenant, $subscription->toArray());
        }

        return $tenant->fresh()?->subscribed() ?? false;
    }

    /**
     * Upsert one Stripe subscription payload.
     *
     * Mirrors Cashier\Http\Controllers\WebhookController so both paths converge
     * on the same rows.
     *
     * @param  array<string, mixed>  $data
     */
    public function mirror(Tenant $tenant, array $data): void
    {
        $items = $data['items']['data'] ?? [];
        $firstItem = $items[0] ?? null;
        $isSinglePrice = count($items) === 1;

        /** @var Subscription $subscription */
        $subscription = $tenant->subscriptions()->updateOrCreate(
            ['stripe_id' => $data['id']],
            [
                'type' => $data['metadata']['type'] ?? $data['metadata']['name'] ?? 'default',
                'stripe_status' => $data['status'],
                'stripe_price' => $isSinglePrice ? $firstItem['price']['id'] : null,
                'quantity' => $isSinglePrice ? ($firstItem['quantity'] ?? null) : null,
                'trial_ends_at' => isset($data['trial_end'])
                    ? Carbon::createFromTimestamp($data['trial_end'])
                    : null,
                'ends_at' => $this->endsAt($data),
            ],
        );

        foreach ($items as $item) {
            $subscription->items()->updateOrCreate(
                ['stripe_id' => $item['id']],
                [
                    'stripe_product' => $item['price']['product'],
                    'stripe_price' => $item['price']['id'],
                    'quantity' => $item['quantity'] ?? null,
                ],
            );
        }

        $this->syncPlan($tenant, $isSinglePrice && $firstItem !== null ? $firstItem['price']['id'] : null);

        // A paid subscription supersedes the card-free trial. Leaving the generic
        // trial in place would keep granting access after a cancellation.
        if ($subscription->valid() && $tenant->trial_ends_at !== null) {
            $tenant->forceFill(['trial_ends_at' => null])->save();
        }
    }

    /**
     * When the subscription ends — the field Cashier actually reads to decide
     * whether access is still valid.
     *
     * Cashier treats a null ends_at as "active" regardless of stripe_status, so a
     * terminated subscription MUST carry a date here or it keeps unlocking the
     * app forever.
     *
     * @param  array<string, mixed>  $data
     */
    private function endsAt(array $data): ?Carbon
    {
        if (in_array($data['status'], ['canceled', 'incomplete_expired'], true)) {
            return isset($data['ended_at'])
                ? Carbon::createFromTimestamp($data['ended_at'])
                : Carbon::now();
        }

        // Scheduled to stop: access continues until the paid period runs out.
        if (($data['cancel_at_period_end'] ?? false) && isset($data['current_period_end'])) {
            return Carbon::createFromTimestamp($data['current_period_end']);
        }

        return null;
    }

    private function syncPlan(Tenant $tenant, ?string $priceId): void
    {
        if ($priceId === null) {
            return;
        }

        $plan = Plan::query()->where('stripe_price_id', $priceId)->first();

        if ($plan !== null && $tenant->plan_slug !== $plan->slug) {
            $tenant->update(['plan_slug' => $plan->slug->value]);
        }
    }
}
