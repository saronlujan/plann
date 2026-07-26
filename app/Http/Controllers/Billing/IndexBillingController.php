<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexBillingController extends Controller
{
    public function __invoke(Request $request, BillingService $billing): Response
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        return Inertia::render('Billing/Index', [
            'plans' => $billing->plans()
                ->map(fn (Plan $plan): array => [
                    'slug' => $plan->slug->value,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'max_users' => $plan->max_users,
                    'monthly_price_cents' => $plan->monthly_price_cents,
                    'annual_price_cents' => $plan->annualPriceCents(),
                    'available' => $plan->stripe_price_id !== null,
                ])
                ->all(),
            'status' => $billing->status($tenant),
            'invoices' => $billing->invoices($tenant),
        ]);
    }
}
