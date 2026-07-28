<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Attend un tableau ['user' => User, 'last_message' => Message,
 * 'unread_count' => int] — pas de modèle Eloquent dédié, les
 * conversations sont dérivées des messages (voir MessageService).
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this['user'];
        $lastMessage = $this['last_message'];

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'last_message' => [
                'id' => $lastMessage->id,
                'content' => $lastMessage->content,
                'sender_id' => $lastMessage->sender_id,
                'is_read' => $lastMessage->is_read,
                'created_at' => $lastMessage->created_at?->toIso8601String(),
            ],
            'unread_count' => $this['unread_count'],
        ];
    }
}
