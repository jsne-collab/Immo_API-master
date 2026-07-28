<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lease_id' => Lease::factory(),
            'property_id' => fn (array $attributes) => Lease::find($attributes['lease_id'])?->property_id,
            'tenant_id' => fn (array $attributes) => Lease::find($attributes['lease_id'])?->tenant_id,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => MaintenanceRequest::STATUS_NEW,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => ['status' => MaintenanceRequest::STATUS_IN_PROGRESS]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => MaintenanceRequest::STATUS_RESOLVED]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => MaintenanceRequest::STATUS_REJECTED]);
    }
}
