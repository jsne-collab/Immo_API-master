<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lease_id' => Lease::factory(),
            'property_id' => fn (array $attributes) => Lease::find($attributes['lease_id'])?->property_id,
            'sender_id' => fn (array $attributes) => Lease::find($attributes['lease_id'])?->owner_id,
            'receiver_id' => fn (array $attributes) => Lease::find($attributes['lease_id'])?->tenant_id,
            'content' => fake()->sentence(),
            'is_read' => false,
        ];
    }

    public function fromTenant(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_id' => fn (array $attrs) => Lease::find($attrs['lease_id'])?->tenant_id,
            'receiver_id' => fn (array $attrs) => Lease::find($attrs['lease_id'])?->owner_id,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['is_read' => true]);
    }
}
