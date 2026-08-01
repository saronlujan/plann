<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'tenants' => Tenant::query()->count(),
                'subscribers' => $this->activeSubscriptions()->count(),
                'trialing' => Tenant::query()
                    ->whereNotNull('trial_ends_at')
                    ->where('trial_ends_at', '>', now())
                    ->whereDoesntHave('subscriptions', fn ($query) => $query
                        ->whereIn('stripe_status', ['active', 'trialing']))
                    ->count(),
                'monthly_revenue_cents' => $this->monthlyRevenueCents(),
            ],
            // Newest first: what the platform owner opens this page to see is who
            // just arrived.
            'recent' => Tenant::query()
                ->with('users:id,tenant_id,email')
                ->latest('id')
                ->limit(8)
                ->get(['id', 'name', 'plan_slug', 'trial_ends_at', 'created_at'])
                ->map(fn (Tenant $tenant): array => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->users->first()?->email,
                    'plan' => $tenant->plan_slug->label(),
                    'created_at' => $tenant->created_at?->toDateString(),
                ])
                ->all(),
        ]);
    }

    /**
     * Subscriptions Stripe currently considers live.
     *
     * `trialing` counts: the card is on file and the money is already scheduled,
     * which is what separates it from the app's own card-free trial.
     *
     * @return Builder
     */
    private function activeSubscriptions()
    {
        return DB::table('subscriptions')->whereIn('stripe_status', ['active', 'trialing']);
    }

    /**
     * What the platform bills in a month, in cents.
     *
     * Read from the price each subscription is actually on rather than from the
     * tenant's plan slug: the slug says what they signed up for, the Stripe price
     * says what they are charged, and a price change would drift the two apart.
     *
     * This is recurring revenue, not money received — Cashier keeps no record of
     * payments locally, so a true total would have to come from Stripe.
     */
    private function monthlyRevenueCents(): int
    {
        return (int) $this->activeSubscriptions()
            ->join('plans', 'plans.stripe_price_id', '=', 'subscriptions.stripe_price')
            ->sum(DB::raw('plans.monthly_price_cents * coalesce(subscriptions.quantity, 1)'));
    }
}
