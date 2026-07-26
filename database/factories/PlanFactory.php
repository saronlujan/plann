<?php

namespace Database\Factories;

use App\Enums\PlanSlug;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => PlanSlug::Basic->value,
            'name' => PlanSlug::Basic->label(),
            'description' => PlanSlug::Basic->description(),
            'monthly_price_cents' => PlanSlug::Basic->monthlyPriceCents(),
            'max_users' => PlanSlug::Basic->maxUsers(),
            'stripe_price_id' => null,
            'sort_order' => PlanSlug::Basic->sortOrder(),
            'is_active' => true,
        ];
    }

    public function pro(): static
    {
        return $this->state(fn (): array => [
            'slug' => PlanSlug::Pro->value,
            'name' => PlanSlug::Pro->label(),
            'description' => PlanSlug::Pro->description(),
            'monthly_price_cents' => PlanSlug::Pro->monthlyPriceCents(),
            'max_users' => PlanSlug::Pro->maxUsers(),
            'sort_order' => PlanSlug::Pro->sortOrder(),
        ]);
    }
}
