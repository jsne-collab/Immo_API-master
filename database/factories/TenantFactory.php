<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->tenant(),
            'guarantor_name' => fake()->name(),
            'guarantor_phone' => fake()->numerify('+228#########'),
            'occupation' => fake()->jobTitle(),
            'monthly_income' => fake()->numberBetween(50000, 500000),
        ];
    }
}
