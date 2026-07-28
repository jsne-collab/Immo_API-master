<?php

namespace App\Policies;

use App\Models\PropertyUnit;
use App\Models\User;

class PropertyUnitPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, PropertyUnit $propertyUnit): bool
    {
        return $user->is($propertyUnit->property->owner) || $propertyUnit->isAvailable();
    }

    public function update(User $user, PropertyUnit $propertyUnit): bool
    {
        return $user->is($propertyUnit->property->owner);
    }

    public function delete(User $user, PropertyUnit $propertyUnit): bool
    {
        return $user->is($propertyUnit->property->owner);
    }
}
