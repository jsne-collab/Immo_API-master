<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Le propriétaire du bien enregistre un paiement manuellement (espèces,
     * virement confirmé hors app...) ; POST payments/initiate est réservé
     * au locataire (voir PaymentController::initiate).
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->is($payment->tenant) || $user->is($payment->lease->owner);
    }

    /**
     * Seul le propriétaire valide/ajuste le statut d'un paiement — un
     * locataire ne doit jamais pouvoir s'auto-valider un paiement.
     */
    public function update(User $user, Payment $payment): bool
    {
        return $user->is($payment->lease->owner);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->is($payment->lease->owner);
    }
}
