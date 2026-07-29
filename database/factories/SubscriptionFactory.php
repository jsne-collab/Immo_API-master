<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-2 months', 'now');

        return [
            'owner_id' => User::factory()->owner(),
            'amount' => config('subscription.amount'),
            'period_start' => $start,
            'period_end' => (clone $start)->modify('+'.config('subscription.period_months').' months'),
            'payment_method_id' => null,
            'status' => Subscription::STATUS_PAID,
            'paid_at' => $start,
            'reference' => fake()->bothify('SUB-########'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_PENDING,
            'paid_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $start = fake()->dateTimeBetween('-6 months', '-3 months');

            return [
                'period_start' => $start,
                'period_end' => (clone $start)->modify('+'.config('subscription.period_months').' months'),
                'status' => Subscription::STATUS_PAID,
                'paid_at' => $start,
            ];
        });
    }
}
