<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'type' => $this->type,
            'address' => $this->address,
            'city' => $this->city,
            'surface_area' => $this->surface_area !== null ? (float) $this->surface_area : null,
            'rooms_count' => $this->rooms_count,
            'monthly_rent' => (float) $this->monthly_rent,
            'deposit_amount' => (float) $this->deposit_amount,
            'status' => $this->status,
            'description' => $this->description,
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'units' => PropertyUnitResource::collection($this->whenLoaded('units')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
