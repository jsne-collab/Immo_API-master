<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $monthlyRent = fake()->numberBetween(30000, 300000);

        return [
            'property_id' => Property::factory()->rented(),
            'unit_id' => null,
            'tenant_id' => User::factory()->tenant(),
            'owner_id' => User::factory()->owner(),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'end_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
            'monthly_rent' => $monthlyRent,
            'deposit_amount' => $monthlyRent,
            'status' => Lease::STATUS_ACTIVE,
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Lease::STATUS_TERMINATED]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Lease::STATUS_EXPIRED,
            'start_date' => fake()->dateTimeBetween('-2 years', '-1 year'),
            'end_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}
