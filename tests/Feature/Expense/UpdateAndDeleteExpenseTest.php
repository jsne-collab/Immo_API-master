<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAndDeleteExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_update_their_expense(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $expense = Expense::factory()->create(['property_id' => $property->id, 'owner_id' => $owner->id, 'amount' => 10000]);

        $response = $this->actingAs($owner, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'amount' => 15000,
        ]);

        $response->assertOk()->assertJsonPath('data.amount', 15000);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 15000]);
    }

    public function test_an_unrelated_owner_cannot_update_the_expense(): void
    {
        $stranger = User::factory()->owner()->create();
        $expense = Expense::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'amount' => 15000,
        ]);

        $response->assertStatus(403);
    }

    public function test_update_requires_authentication(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->putJson("/api/v1/expenses/{$expense->id}", ['amount' => 15000]);

        $response->assertStatus(401);
    }

    public function test_the_owner_can_delete_their_expense(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $expense = Expense::factory()->create(['property_id' => $property->id, 'owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->deleteJson("/api/v1/expenses/{$expense->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_an_unrelated_owner_cannot_delete_the_expense(): void
    {
        $stranger = User::factory()->owner()->create();
        $expense = Expense::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->deleteJson("/api/v1/expenses/{$expense->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }
}
