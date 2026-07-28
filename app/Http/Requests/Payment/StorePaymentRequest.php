<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lease_id' => ['required', 'integer', 'exists:leases,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'payment_date' => ['required', 'date'],
            'period_covered' => ['required', 'string', 'max:20'],
            'status' => ['sometimes', 'in:pending,validated,late,partial'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
