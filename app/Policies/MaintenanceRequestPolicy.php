<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    /**
     * Admin bypass: admins can do everything, other checks fall through otherwise.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isTenant() || $user->isOwner();
    }

    /**
     * Determine whether the user can view the model (and post comments on it).
     */
    public function view(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return $user->is($maintenanceRequest->tenant) || $user->is($maintenanceRequest->lease->owner);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isTenant();
    }

    /**
     * Determine whether the user can update the model's content (title/description/priority).
     * Further restricted to status === 'new' in the Service layer.
     */
    public function update(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return $maintenanceRequest->tenant_id === $user->id;
    }

    /**
     * Determine whether the user can update the model's status.
     * Reserved to the owner of the property.
     */
    public function updateStatus(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return $user->is($maintenanceRequest->lease->owner);
    }

    /**
     * Determine whether the user can delete the model.
     * Further restricted to status === 'new' in the Service layer.
     */
    public function delete(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return $maintenanceRequest->tenant_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return false;
    }
}
