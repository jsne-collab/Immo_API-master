<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageRepository
{
    private const WITH = ['sender', 'receiver'];

    /**
     * Tous les messages impliquant l'utilisateur, du plus récent au plus
     * ancien — regroupés par interlocuteur dans le Service (pas de table
     * `conversations` dédiée).
     */
    public function allForUser(User $user): Collection
    {
        return Message::with(self::WITH)
            ->where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function threadBetween(User $user, int $otherUserId, int $perPage = 50): LengthAwarePaginator
    {
        return Message::with(self::WITH)
            ->where(function ($query) use ($user, $otherUserId) {
                $query->where('sender_id', $user->id)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($query) use ($user, $otherUserId) {
                $query->where('sender_id', $otherUserId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Message
    {
        return Message::create($data)->load(self::WITH);
    }

    public function markRead(Message $message): Message
    {
        $message->update(['is_read' => true]);

        return $message->fresh(self::WITH);
    }

    public function markThreadRead(User $receiver, int $otherUserId): void
    {
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $receiver->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function delete(Message $message): void
    {
        $message->delete();
    }
}
