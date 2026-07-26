<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Goal;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'currency_id' => Currency::factory(),
            'name' => fake()->words(2, true),
            'target_amount' => fake()->randomFloat(2, 1000, 20000),
            'current_amount' => 0,
            'target_date' => fake()->optional()->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
        ];
    }
}
