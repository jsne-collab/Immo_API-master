<?php

namespace App\Policies;

use App\Models\Lease;
use App\Models\User;

class LeasePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Un bail est visible par le propriétaire du bien concerné et par le
     * locataire qui y est rattaché — personne d'autre (règle A.6.8).
     */
    public function view(User $user, Lease $lease): bool
    {
        return $user->is($lease->owner) || $user->is($lease->tenant);
    }

    /**
     * Modifier/résilier/renouveler/supprimer un bail reste une prérogative
     * du propriétaire uniquement.
     */
    public function update(User $user, Lease $lease): bool
    {
        return $user->is($lease->owner);
    }
}
