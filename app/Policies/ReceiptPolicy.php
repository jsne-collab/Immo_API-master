<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
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
     * Une quittance est visible par le locataire concerné et le
     * propriétaire du bail, comme le paiement dont elle découle.
     */
    public function view(User $user, Receipt $receipt): bool
    {
        return $user->is($receipt->payment->tenant) || $user->is($receipt->payment->lease->owner);
    }
}
