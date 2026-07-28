<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->is($expense->owner);
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->is($expense->owner);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->is($expense->owner);
    }
}
