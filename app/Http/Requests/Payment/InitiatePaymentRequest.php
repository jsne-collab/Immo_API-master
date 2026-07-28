<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTenant();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lease_id' => ['required', 'integer', 'exists:leases,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['sometimes', 'date'],
            'period_covered' => ['required', 'string', 'max:20'],
            'reference' => ['nullable', 'string', 'max:255'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'method_type' => ['sometimes', 'required_without:payment_method_id', 'in:mobile_money,bank_transfer,cash'],
            'method_provider' => ['nullable', 'string', 'max:255'],
            'method_account_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
