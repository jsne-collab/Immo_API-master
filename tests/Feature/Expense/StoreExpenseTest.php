<?php

namespace Tests\Feature\Expense;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_record_an_expense_for_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/expenses', [
            'property_id' => $property->id,
            'category' => 'maintenance',
            'amount' => 25000,
            'expense_date' => now()->toDateString(),
            'description' => 'Réparation plomberie',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category', 'maintenance')
            ->assertJsonPath('data.amount', 25000);

        $this->assertDatabaseHas('expenses', [
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'category' => 'maintenance',
        ]);
    }

    public function test_a_tenant_cannot_record_an_expense(): void
    {
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($tenant, 'sanctum')->postJson('/api/v1/expenses', [
            'property_id' => $property->id,
            'amount' => 25000,
            'expense_date' => now()->toDateString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_an_owner_cannot_record_an_expense_for_another_owners_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/expenses', [
            'property_id' => $property->id,
            'amount' => 25000,
            'expense_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id']);
    }

    public function test_store_fails_with_missing_fields(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/expenses', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id', 'amount', 'expense_date']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/expenses', []);

        $response->assertStatus(401);
    }
}
