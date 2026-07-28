<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->tenant(),
            'type' => PaymentMethod::TYPE_MOBILE_MONEY,
            'provider' => fake()->randomElement(['Flooz', 'T-Money']),
            'account_number' => fake()->numerify('+228#########'),
            'is_default' => true,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethod::TYPE_CASH,
            'provider' => null,
            'account_number' => null,
        ]);
    }
}
