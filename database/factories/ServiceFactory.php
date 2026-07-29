<?php

namespace Database\Factories;

use App\Enums\LabelColor;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
            'default_price' => null,
            'currency_id' => null,
            'color' => fake()->randomElement(LabelColor::cases())->value,
        ];
    }

    /**
     * A service offered at a standing price, which is what gets suggested when it
     * is appended to a transaction.
     */
    public function priced(string $price, int $currencyId): self
    {
        return $this->state(fn (array $attributes): array => [
            'default_price' => $price,
            'currency_id' => $currencyId,
        ]);
    }
}
