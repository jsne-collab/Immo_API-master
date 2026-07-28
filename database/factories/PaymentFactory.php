<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lease_id' => Lease::factory(),
            'tenant_id' => fn (array $attributes) => Lease::find($attributes['lease_id'])?->tenant_id,
            'amount' => fake()->numberBetween(30000, 300000),
            'payment_method_id' => null,
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'period_covered' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m'),
            'status' => Payment::STATUS_VALIDATED,
            'reference' => fake()->bothify('PAY-########'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Payment::STATUS_PENDING]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Payment::STATUS_LATE]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Payment::STATUS_PARTIAL]);
    }
}
