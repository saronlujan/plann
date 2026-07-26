<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => Category::factory(),
            'currency_id' => Currency::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
        ];
    }
}
