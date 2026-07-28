<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Message $message): bool
    {
        return $user->is($message->sender) || $user->is($message->receiver);
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isTenant();
    }

    /**
     * Seul le destinataire marque un message comme lu — l'auteur n'a pas
     * à confirmer la lecture de son propre message.
     */
    public function markRead(User $user, Message $message): bool
    {
        return $user->is($message->receiver);
    }

    /**
     * Seul l'auteur peut retirer son propre message.
     */
    public function delete(User $user, Message $message): bool
    {
        return $user->is($message->sender);
    }
}
