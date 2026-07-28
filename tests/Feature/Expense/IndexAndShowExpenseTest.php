<?php

namespace Tests\Feature\Expense;

use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAndShowExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_only_sees_their_own_expenses(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        Expense::factory()->create(['property_id' => $property->id, 'owner_id' => $owner->id]);
        Expense::factory()->create();

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/expenses');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_index_can_filter_by_property_id(): void
    {
        $owner = User::factory()->owner()->create();
        $propertyA = Property::factory()->create(['owner_id' => $owner->id]);
        $propertyB = Property::factory()->create(['owner_id' => $owner->id]);
        Expense::factory()->create(['property_id' => $propertyA->id, 'owner_id' => $owner->id]);
        Expense::factory()->create(['property_id' => $propertyB->id, 'owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/expenses?property_id={$propertyA->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_a_tenant_cannot_list_expenses(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant, 'sanctum')->getJson('/api/v1/expenses');

        $response->assertStatus(403);
    }

    public function test_the_owner_can_view_their_expense(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $expense = Expense::factory()->create(['property_id' => $property->id, 'owner_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson("/api/v1/expenses/{$expense->id}");

        $response->assertOk()->assertJsonPath('data.id', $expense->id);
    }

    public function test_an_unrelated_owner_cannot_view_the_expense(): void
    {
        $stranger = User::factory()->owner()->create();
        $expense = Expense::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/expenses/{$expense->id}");

        $response->assertStatus(403);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/expenses');

        $response->assertStatus(401);
    }
}
