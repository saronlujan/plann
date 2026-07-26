<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Enums\LabelColor;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(CategoryType::cases())->value,
            'color' => fake()->randomElement(LabelColor::cases())->value,
        ];
    }
}
