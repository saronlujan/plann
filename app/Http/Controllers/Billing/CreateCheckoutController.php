<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CreateCheckoutController extends Controller
{
    public function __invoke(Request $request, Plan $plan): Response
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        abort_if($plan->stripe_price_id === null, 422, 'Plano indisponível para assinatura no momento.');

        // Already subscribed → swap the plan in place (prorated by Stripe).
        if ($tenant->subscribed()) {
            $tenant->subscription('default')?->swap($plan->stripe_price_id);
            $tenant->update(['plan_slug' => $plan->slug->value]);

            return back();
        }

        $builder = $tenant->newSubscription('default', $plan->stripe_price_id);

        if ($tenant->onTrial()) {
            $builder->trialUntil($tenant->trial_ends_at);
        }

        $checkout = $builder->checkout([
            // The flag tells the billing page to pull the subscription straight
            // from Stripe instead of waiting for the webhook to land.
            'success_url' => route('billing.index', ['checkout' => 'success']),
            'cancel_url' => route('billing.index', ['checkout' => 'cancelled']),
        ]);

        // Send the browser to Stripe Checkout (works for both Inertia and plain requests).
        return Inertia::location($checkout->asStripeCheckoutSession()->url);
    }
}
