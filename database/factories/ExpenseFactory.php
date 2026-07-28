<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'owner_id' => fn (array $attributes) => Property::find($attributes['property_id'])?->owner_id,
            'category' => fake()->randomElement([
                Expense::CATEGORY_MAINTENANCE,
                Expense::CATEGORY_TAX,
                Expense::CATEGORY_INSURANCE,
                Expense::CATEGORY_OTHER,
            ]),
            'amount' => fake()->numberBetween(5000, 200000),
            'expense_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'description' => fake()->sentence(),
        ];
    }
}
