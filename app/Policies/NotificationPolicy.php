<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->is($notification->user);
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->is($notification->user);
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->is($notification->user);
    }
}
