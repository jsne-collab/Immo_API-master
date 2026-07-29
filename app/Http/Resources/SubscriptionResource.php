<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'period_start' => $this->period_start->toDateString(),
            'period_end' => $this->period_end->toDateString(),
            'payment_method' => $this->paymentMethod ? [
                'id' => $this->paymentMethod->id,
                'type' => $this->paymentMethod->type,
                'provider' => $this->paymentMethod->provider,
            ] : null,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'reference' => $this->reference,
        ];
    }
}
