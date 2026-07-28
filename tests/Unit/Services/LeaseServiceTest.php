<?php

namespace Tests\Unit\Services;

use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Services\LeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_overdue_leases_flips_status_and_frees_the_property(): void
    {
        $property = Property::factory()->rented()->create();
        $lease = Lease::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $property->owner_id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => Lease::STATUS_ACTIVE,
        ]);

        $count = app(LeaseService::class)->expireOverdueLeases();

        $this->assertSame(1, $count);
        $this->assertSame(Lease::STATUS_EXPIRED, $lease->fresh()->status);
        $this->assertSame(Property::STATUS_AVAILABLE, $property->fresh()->status);
    }

    public function test_expire_overdue_leases_frees_only_the_unit_when_the_lease_targets_one(): void
    {
        $property = Property::factory()->create();
        $unit = PropertyUnit::factory()->rented()->for($property)->create();
        Lease::factory()->create([
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'owner_id' => $property->owner_id,
            'end_date' => now()->subDay()->toDateString(),
            'status' => Lease::STATUS_ACTIVE,
        ]);

        app(LeaseService::class)->expireOverdueLeases();

        $this->assertSame(PropertyUnit::STATUS_AVAILABLE, $unit->fresh()->status);
        $this->assertSame(Property::STATUS_AVAILABLE, $property->fresh()->status);
    }

    public function test_expire_overdue_leases_ignores_leases_still_within_their_term(): void
    {
        $lease = Lease::factory()->create([
            'end_date' => now()->addMonth()->toDateString(),
            'status' => Lease::STATUS_ACTIVE,
        ]);

        $count = app(LeaseService::class)->expireOverdueLeases();

        $this->assertSame(0, $count);
        $this->assertSame(Lease::STATUS_ACTIVE, $lease->fresh()->status);
    }

    public function test_expire_overdue_leases_ignores_leases_already_terminated(): void
    {
        $lease = Lease::factory()->terminated()->create([
            'end_date' => now()->subMonth()->toDateString(),
        ]);

        $count = app(LeaseService::class)->expireOverdueLeases();

        $this->assertSame(0, $count);
        $this->assertSame(Lease::STATUS_TERMINATED, $lease->fresh()->status);
    }
}
