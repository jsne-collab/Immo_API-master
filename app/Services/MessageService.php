<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\MessageRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messages,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Regroupe les messages de l'utilisateur par interlocuteur (pas de
     * table `conversations` dédiée) : dernier message + nombre de
     * messages non lus par conversation, la plus récente en premier.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listConversations(User $user): array
    {
        $conversations = [];

        foreach ($this->messages->allForUser($user) as $message) {
            $otherUser = $message->sender_id === $user->id ? $message->receiver : $message->sender;

            if (! isset($conversations[$otherUser->id])) {
                $conversations[$otherUser->id] = [
                    'user' => $otherUser,
                    'last_message' => $message,
                    'unread_count' => 0,
                ];
            }

            if ($message->receiver_id === $user->id && ! $message->is_read) {
                $conversations[$otherUser->id]['unread_count']++;
            }
        }

        return array_values($conversations);
    }

    public function messagesForConversation(User $user, int $otherUserId, int $perPage = 50): LengthAwarePaginator
    {
        $thread = $this->messages->threadBetween($user, $otherUserId, $perPage);

        $this->messages->markThreadRead($user, $otherUserId);

        return $thread;
    }

    /**
     * Un message ne peut être envoyé qu'entre un propriétaire et un
     * locataire liés par (au moins) un bail, dans un sens ou l'autre.
     *
     * @param  array<string, mixed>  $data
     */
    public function send(User $sender, array $data): Message
    {
        $receiver = User::findOrFail($data['receiver_id']);

        $lease = Lease::where(function ($query) use ($sender, $receiver) {
            $query->where('owner_id', $sender->id)->where('tenant_id', $receiver->id);
        })->orWhere(function ($query) use ($sender, $receiver) {
            $query->where('owner_id', $receiver->id)->where('tenant_id', $sender->id);
        })->latest()->first();

        if (! $lease) {
            throw ValidationException::withMessages([
                'receiver_id' => ['Vous ne pouvez écrire qu\'à un propriétaire ou un locataire avec qui vous avez un bail.'],
            ]);
        }

        $message = $this->messages->create([
            'lease_id' => $data['lease_id'] ?? $lease->id,
            'property_id' => $data['property_id'] ?? $lease->property_id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'content' => $data['content'],
            'is_read' => false,
        ]);

        $this->notifications->notify(
            $receiver,
            Notification::TYPE_NEW_MESSAGE,
            "Nouveau message de {$sender->name}",
            Str::limit($data['content'], 100),
        );

        return $message;
    }

    public function markRead(Message $message): Message
    {
        return $this->messages->markRead($message);
    }

    public function delete(Message $message): void
    {
        $this->messages->delete($message);
    }
}
