<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;

class SyncStripePlans extends Command
{
    protected $signature = 'app:sync-stripe-plans';

    protected $description = 'Create/update Stripe products and annual prices for each plan and store the price IDs.';

    public function handle(): int
    {
        if (blank(config('cashier.secret'))) {
            $this->error('STRIPE_SECRET is not configured.');

            return self::FAILURE;
        }

        $stripe = Cashier::stripe();
        $currency = config('cashier.currency', 'brl');

        foreach (Plan::query()->orderBy('sort_order')->get() as $plan) {
            $product = $stripe->products->create([
                'name' => config('app.name').' — '.$plan->name,
                'metadata' => ['plan_slug' => $plan->slug->value],
            ]);

            $price = $stripe->prices->create([
                'product' => $product->id,
                'currency' => $currency,
                'unit_amount' => $plan->annualPriceCents(),
                'recurring' => ['interval' => 'year'],
                'metadata' => ['plan_slug' => $plan->slug->value],
            ]);

            $plan->update(['stripe_price_id' => $price->id]);

            $this->info(sprintf('%s → %s', $plan->slug->value, $price->id));
        }

        return self::SUCCESS;
    }
}
