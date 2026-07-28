<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'profile_completed' => $this->profile_completed,
            'profile' => [
                'avatar_url' => $this->profile?->avatar
                    ? Storage::disk('public')->url($this->profile->avatar)
                    : $this->avatar_url,
                'address' => $this->profile?->address,
                'city' => $this->profile?->city,
                'id_card_number' => $this->profile?->id_card_number,
                'date_of_birth' => $this->profile?->date_of_birth?->toDateString(),
            ],
        ];
    }
}
