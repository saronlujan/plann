<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'currency_id' => Currency::factory(),
            'name' => fake()->words(2, true),
        ];
    }
}
