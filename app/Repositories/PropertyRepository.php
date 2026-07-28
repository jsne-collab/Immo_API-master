<?php

namespace App\Repositories;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertyRepository
{
    public function paginateForOwner(int $ownerId, int $perPage = 15): LengthAwarePaginator
    {
        return Property::with('images')
            ->where('owner_id', $ownerId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateAvailable(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Property::with('images')->where('status', Property::STATUS_AVAILABLE);

        if (! empty($filters['city'])) {
            $query->where('city', 'like', '%'.$filters['city'].'%');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('monthly_rent', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('monthly_rent', '<=', $filters['max_price']);
        }

        if (! empty($filters['query'])) {
            $query->where(function ($sub) use ($filters) {
                $sub->where('title', 'like', '%'.$filters['query'].'%')
                    ->orWhere('address', 'like', '%'.$filters['query'].'%')
                    ->orWhere('city', 'like', '%'.$filters['query'].'%');
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function findOrFail(int $id): Property
    {
        return Property::with(['images', 'units'])->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Property
    {
        return Property::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Property $property, array $data): Property
    {
        $property->update($data);

        return $property->fresh(['images', 'units']);
    }

    public function delete(Property $property): void
    {
        $property->delete();
    }

    public function addImage(Property $property, string $path, bool $isPrimary): PropertyImage
    {
        if ($isPrimary) {
            $property->images()->update(['is_primary' => false]);
        }

        return $property->images()->create([
            'image_path' => $path,
            'is_primary' => $isPrimary,
        ]);
    }
}
