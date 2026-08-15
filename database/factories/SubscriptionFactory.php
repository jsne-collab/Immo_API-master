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
        $plan = config('subscription.plans.monthly');

        return [
            'owner_id' => User::factory()->owner(),
            'amount' => $plan['amount'],
            'plan' => Subscription::PLAN_MONTHLY,
            'period_start' => $start,
            'period_end' => (clone $start)->modify('+'.$plan['period_months'].' months'),
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

    public function yearly(): static
    {
        return $this->state(function (array $attributes) {
            $plan = config('subscription.plans.yearly');
            $start = $attributes['period_start'] ?? fake()->dateTimeBetween('-2 months', 'now');

            return [
                'amount' => $plan['amount'],
                'plan' => Subscription::PLAN_YEARLY,
                'period_end' => (clone $start)->modify('+'.$plan['period_months'].' months'),
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $start = fake()->dateTimeBetween('-6 months', '-3 months');
            $plan = config('subscription.plans.monthly');

            return [
                'period_start' => $start,
                'period_end' => (clone $start)->modify('+'.$plan['period_months'].' months'),
                'status' => Subscription::STATUS_PAID,
                'paid_at' => $start,
            ];
        });
    }
}
