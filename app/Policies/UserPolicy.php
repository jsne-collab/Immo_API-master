<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Admins bypass every check below; everyone else only ever acts on
     * their own account (règle A.6.8 : un utilisateur ne consulte/modifie
     * que ce qui le concerne directement).
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Un propriétaire peut chercher un locataire (pour lui attribuer un
     * bail) mais uniquement via une recherche ciblée, jamais un listing
     * complet — voir UserService::list() qui impose role=tenant et un
     * terme de recherche non-admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, User $model): bool
    {
        return $user->is($model);
    }

    public function update(User $user, User $model): bool
    {
        return $user->is($model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->is($model);
    }
}
