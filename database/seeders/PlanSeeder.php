<?php

namespace Database\Seeders;

use App\Enums\PlanSlug;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PlanSlug::cases() as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan->value],
                [
                    'name' => $plan->label(),
                    'description' => $plan->description(),
                    'monthly_price_cents' => $plan->monthlyPriceCents(),
                    'max_users' => $plan->maxUsers(),
                    'sort_order' => $plan->sortOrder(),
                    'is_active' => true,
                ],
            );
        }
    }
}
