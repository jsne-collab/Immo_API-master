<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:pending,validated,late,partial'],
            'payment_method_id' => ['sometimes', 'nullable', 'integer', 'exists:payment_methods,id'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
