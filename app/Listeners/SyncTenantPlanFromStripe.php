<?php

namespace App\Listeners;

use App\Enums\PlanSlug;
use App\Models\Tenant;
use App\Services\Billing\SyncStripeSubscriptions;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Keeps the local subscription state in step with Stripe.
 *
 * Runs the same mirroring the checkout return does, so both paths converge on
 * identical rows. The webhook remains the durable channel — it is what catches
 * renewals, failed payments and cancellations that happen with nobody looking at
 * the screen.
 */
class SyncTenantPlanFromStripe
{
    public function __construct(private readonly SyncStripeSubscriptions $sync) {}

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

        $tenant = Tenant::query()->where('stripe_id', $stripeId)->first();

        if ($tenant === null) {
            return;
        }

        // A deletion event carries a status Stripe has already terminated, so the
        // shared mirror closes it out; the plan drops back to the entry tier.
        $this->sync->mirror($tenant, $object);

        if ($type === 'customer.subscription.deleted') {
            $tenant->update(['plan_slug' => PlanSlug::Basic->value]);
        }
    }
}
