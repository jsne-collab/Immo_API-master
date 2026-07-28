<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\PropertyUnit;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertyUnitRepository
{
    public function paginateForProperty(Property $property, int $perPage = 15): LengthAwarePaginator
    {
        return $property->units()->latest()->paginate($perPage);
    }

    public function findOrFail(int $id): PropertyUnit
    {
        return PropertyUnit::with('property')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Property $property, array $data): PropertyUnit
    {
        return $property->units()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyUnit $unit, array $data): PropertyUnit
    {
        $unit->update($data);

        return $unit->fresh();
    }

    public function delete(PropertyUnit $unit): void
    {
        $unit->delete();
    }
}
