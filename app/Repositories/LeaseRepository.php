<?php

namespace App\Repositories;

use App\Models\Lease;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LeaseRepository
{
    private const WITH = ['property', 'unit', 'tenant', 'owner'];

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Lease::with(self::WITH)
            ->where('owner_id', $user->id)
            ->orWhere('tenant_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Lease
    {
        return Lease::with(self::WITH)->findOrFail($id);
    }

    /**
     * Règle A.6.1 : un bien (ou une unité) ne peut être lié qu'à un seul
     * bail actif à la fois.
     */
    public function hasActiveLease(int $propertyId, ?int $unitId, ?int $excludeLeaseId = null): bool
    {
        $query = Lease::where('property_id', $propertyId)
            ->where('status', Lease::STATUS_ACTIVE);

        $query = $unitId !== null ? $query->where('unit_id', $unitId) : $query->whereNull('unit_id');

        if ($excludeLeaseId !== null) {
            $query->where('id', '!=', $excludeLeaseId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lease
    {
        return Lease::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lease $lease, array $data): Lease
    {
        $lease->update($data);

        return $lease->fresh(self::WITH);
    }

    /**
     * Leases actifs dont la date de fin est dépassée (règle A.6.6).
     *
     * @return Collection<int, Lease>
     */
    public function findExpiring(): Collection
    {
        return Lease::where('status', Lease::STATUS_ACTIVE)
            ->whereDate('end_date', '<', now()->toDateString())
            ->get();
    }
}
