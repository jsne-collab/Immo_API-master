<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'role' => $this->sender->role,
            ],
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'role' => $this->receiver->role,
            ],
            'lease_id' => $this->lease_id,
            'property_id' => $this->property_id,
            'content' => $this->content,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
