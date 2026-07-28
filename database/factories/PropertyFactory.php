<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->owner(),
            'title' => fake()->streetName().' - '.fake()->randomElement(['Maison', 'Appartement', 'Studio']),
            'type' => fake()->randomElement(['maison', 'appartement', 'studio', 'chambre']),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Lomé', 'Kara', 'Sokodé', 'Kpalimé']),
            'surface_area' => fake()->randomFloat(2, 20, 300),
            'rooms_count' => fake()->numberBetween(1, 6),
            'monthly_rent' => fake()->numberBetween(30000, 500000),
            'deposit_amount' => fake()->numberBetween(30000, 500000),
            'status' => Property::STATUS_AVAILABLE,
            'description' => fake()->paragraph(),
        ];
    }

    public function rented(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Property::STATUS_RENTED]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Property::STATUS_MAINTENANCE]);
    }
}
