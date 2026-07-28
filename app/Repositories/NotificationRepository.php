<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCountForUser(User $user): int
    {
        return Notification::where('user_id', $user->id)->where('is_read', false)->count();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function markRead(Notification $notification): Notification
    {
        $notification->update(['is_read' => true]);

        return $notification->fresh();
    }

    public function markAllReadForUser(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function delete(Notification $notification): void
    {
        $notification->delete();
    }
}
