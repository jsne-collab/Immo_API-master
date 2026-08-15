<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class InitiateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan' => ['sometimes', 'in:monthly,yearly'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'method_type' => ['sometimes', 'required_without:payment_method_id', 'in:mobile_money,bank_transfer,cash'],
            'method_provider' => ['nullable', 'string', 'max:255'],
            'method_account_number' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
