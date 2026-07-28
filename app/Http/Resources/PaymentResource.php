<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lease' => [
                'id' => $this->lease->id,
                'property_title' => $this->lease->property->title,
            ],
            'tenant' => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ],
            'amount' => (float) $this->amount,
            'payment_method' => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'type' => $this->paymentMethod->type,
                'provider' => $this->paymentMethod->provider,
            ] : null,
            'payment_date' => $this->payment_date->toDateString(),
            'period_covered' => $this->period_covered,
            'status' => $this->status,
            'reference' => $this->reference,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
