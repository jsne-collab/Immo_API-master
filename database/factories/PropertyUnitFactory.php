<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyUnit>
 */
class PropertyUnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'unit_name' => 'Unité '.fake()->bothify('??-##'),
            'floor' => (string) fake()->numberBetween(0, 5),
            'rooms_count' => fake()->numberBetween(1, 4),
            'monthly_rent' => fake()->numberBetween(20000, 200000),
            'status' => PropertyUnit::STATUS_AVAILABLE,
        ];
    }

    public function rented(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PropertyUnit::STATUS_RENTED]);
    }
}
