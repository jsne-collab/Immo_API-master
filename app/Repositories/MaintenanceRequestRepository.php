<?php

namespace App\Repositories;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class MaintenanceRequestRepository
{
    private const WITH = ['property', 'lease', 'tenant'];

    /**
     * @param  array<string, mixed>  $filters  ['status' => ..., 'priority' => ..., 'property_id' => ...]
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MaintenanceRequest::with(self::WITH)
            ->where(function (Builder $q) use ($user) {
                $q->where('tenant_id', $user->id)
                    ->orWhereHas('lease', function (Builder $leaseQuery) use ($user) {
                        $leaseQuery->where('owner_id', $user->id);
                    });
            });

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($perPage);
    }

    public function findOrFail(int $id): MaintenanceRequest
    {
        return MaintenanceRequest::with([...self::WITH, 'comments.user'])->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MaintenanceRequest
    {
        return MaintenanceRequest::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MaintenanceRequest $maintenanceRequest, array $data): MaintenanceRequest
    {
        $maintenanceRequest->update($data);

        return $maintenanceRequest->fresh(self::WITH);
    }

    public function delete(MaintenanceRequest $maintenanceRequest): void
    {
        $maintenanceRequest->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addComment(MaintenanceRequest $maintenanceRequest, array $data): MaintenanceRequest
    {
        $maintenanceRequest->comments()->create($data);

        return $maintenanceRequest->fresh(['comments.user']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }
    }
}
