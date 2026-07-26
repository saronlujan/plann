<?php

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company(),
            'type' => fake()->randomElement(ContactType::cases())->value,
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'document' => fake()->numerify('##.###.###/####-##'),
            'notes' => null,
        ];
    }
}
