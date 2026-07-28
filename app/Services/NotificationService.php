<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\NotificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->notifications->paginateForUser($user, $perPage);
    }

    public function unreadCount(User $user): int
    {
        return $this->notifications->unreadCountForUser($user);
    }

    /**
     * Point d'entrée générique utilisé par les autres Services pour
     * notifier un utilisateur lors d'un événement clé (paiement validé,
     * nouveau message, demande de maintenance...).
     */
    public function notify(User $user, string $type, string $title, string $message): Notification
    {
        return $this->notifications->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }

    public function markRead(Notification $notification): Notification
    {
        return $this->notifications->markRead($notification);
    }

    public function markAllRead(User $user): int
    {
        return $this->notifications->markAllReadForUser($user);
    }

    public function delete(Notification $notification): void
    {
        $this->notifications->delete($notification);
    }
}
