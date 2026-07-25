<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'account_id' => Account::factory(),
            'currency_id' => Currency::factory(),
            'movement_type' => 'expense',
            'type' => 'unique',
            'installment_frequency' => null,
            'installments_total' => null,
            'installment_number' => null,
            'interest_amount' => null,
            'attachment_path' => null,
            'series_uuid' => fake()->uuid(),
            'effective_date' => fake()->date(),
            'effective_until' => null,
            'adjustment_month' => null,
            'amount' => fake()->randomFloat(2, 10, 1000),
            'adjustment_amount' => 0,
            'description' => fake()->sentence(3),
        ];
    }
}
