<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyUnit;
use App\Repositories\PropertyUnitRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertyUnitService
{
    public function __construct(private readonly PropertyUnitRepository $units) {}

    public function listForProperty(Property $property, int $perPage = 15): LengthAwarePaginator
    {
        return $this->units->paginateForProperty($property, $perPage);
    }

    public function show(int $id): PropertyUnit
    {
        return $this->units->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Property $property, array $data): PropertyUnit
    {
        $data['status'] ??= PropertyUnit::STATUS_AVAILABLE;

        return $this->units->create($property, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyUnit $unit, array $data): PropertyUnit
    {
        return $this->units->update($unit, $data);
    }

    public function delete(PropertyUnit $unit): void
    {
        $this->units->delete($unit);
    }
}
