<?php

namespace App\Listeners;

use App\Enums\PlanSlug;
use App\Models\Plan;
use App\Models\Tenant;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class SyncTenantPlanFromStripe
{
    /**
     * Keep the tenant's plan_slug (seat limit) in sync with its Stripe subscription.
     */
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? '';

        if (! in_array($type, [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
        ], true)) {
            return;
        }

        $object = $event->payload['data']['object'] ?? [];
        $stripeId = $object['customer'] ?? null;

        if ($stripeId === null) {
            return;
        }

        $tenant = Cashier::findBillable($stripeId);

        if (! $tenant instanceof Tenant) {
            return;
        }

        if ($type === 'customer.subscription.deleted') {
            $tenant->update(['plan_slug' => PlanSlug::Basic->value]);

            return;
        }

        $priceId = $object['items']['data'][0]['price']['id'] ?? null;
        $plan = $priceId === null ? null : Plan::query()->where('stripe_price_id', $priceId)->first();

        if ($plan !== null) {
            $tenant->update(['plan_slug' => $plan->slug->value]);
        }
    }
}
