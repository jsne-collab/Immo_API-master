<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property' => [
                'id' => $this->property->id,
                'title' => $this->property->title,
                'address' => $this->property->address,
                'city' => $this->property->city,
            ],
            'unit' => $this->unit ? [
                'id' => $this->unit->id,
                'unit_name' => $this->unit->unit_name,
            ] : null,
            'tenant' => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'phone' => $this->tenant->phone,
                'email' => $this->tenant->email,
            ],
            'owner' => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'phone' => $this->owner->phone,
            ],
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'monthly_rent' => (float) $this->monthly_rent,
            'deposit_amount' => (float) $this->deposit_amount,
            'status' => $this->status,
            'contract_pdf_url' => $this->contract_pdf_path
                ? Storage::disk('public')->url($this->contract_pdf_path)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
