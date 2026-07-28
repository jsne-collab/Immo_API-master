<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->payment;

        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'payment' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'period_covered' => $payment->period_covered,
                'payment_date' => $payment->payment_date->toDateString(),
            ],
            'property_title' => $payment->lease->property->title,
            'tenant' => [
                'id' => $payment->tenant->id,
                'name' => $payment->tenant->name,
            ],
            'pdf_url' => Storage::disk('public')->url($this->pdf_path),
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}
