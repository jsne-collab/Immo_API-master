<?php

namespace App\Policies;

use App\Models\Lease;
use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Un bien est visible par son propriétaire, par n'importe quel
     * utilisateur authentifié tant qu'il est "available" (vitrine
     * consultable par les locataires en recherche de logement), ou par
     * le locataire qui y a un bail actif (sinon il ne peut pas consulter
     * le détail de son propre logement une fois celui-ci loué).
     */
    public function view(User $user, Property $property): bool
    {
        return $user->is($property->owner)
            || $property->isAvailable()
            || Lease::where('property_id', $property->id)
                ->where('tenant_id', $user->id)
                ->where('status', Lease::STATUS_ACTIVE)
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Property $property): bool
    {
        return $user->is($property->owner);
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->is($property->owner);
    }
}
