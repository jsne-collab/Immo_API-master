<?php

namespace Tests\Unit\Services;

use App\Models\Expense;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Property;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_net_balance_for_property_is_validated_revenue_minus_expenses(): void
    {
        $property = Property::factory()->create();
        $lease = Lease::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $property->owner_id,
        ]);

        Payment::factory()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'status' => 'validated',
            'amount' => 150000,
        ]);
        Payment::factory()->pending()->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'amount' => 150000,
        ]);

        Expense::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $property->owner_id,
            'amount' => 40000,
        ]);

        $balance = app(ExpenseService::class)->netBalanceForProperty($property);

        $this->assertSame(150000.0, $balance['total_revenue']);
        $this->assertSame(40000.0, $balance['total_expenses']);
        $this->assertSame(110000.0, $balance['net_balance']);
    }

    public function test_net_balance_ignores_expenses_and_payments_from_other_properties(): void
    {
        $property = Property::factory()->create();
        Expense::factory()->create();
        Payment::factory()->create(['status' => 'validated']);

        $balance = app(ExpenseService::class)->netBalanceForProperty($property);

        $this->assertSame(0.0, $balance['total_revenue']);
        $this->assertSame(0.0, $balance['total_expenses']);
        $this->assertSame(0.0, $balance['net_balance']);
    }
}
