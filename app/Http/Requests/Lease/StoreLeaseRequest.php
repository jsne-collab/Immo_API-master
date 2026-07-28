<?php

namespace App\Http\Requests\Lease;

use App\Models\Lease;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lease::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('owner_id', $this->user()->id),
            ],
            'unit_id' => [
                'nullable',
                Rule::exists('property_units', 'id')->where('property_id', $this->input('property_id')),
            ],
            'tenant_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', User::ROLE_TENANT),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'guarantor_name' => ['nullable', 'string', 'max:255'],
            'guarantor_phone' => ['nullable', 'string', 'max:20'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.exists' => "Ce bien n'existe pas ou ne vous appartient pas.",
            'unit_id.exists' => "Cette unité n'appartient pas au bien sélectionné.",
            'tenant_id.exists' => "Cet utilisateur n'existe pas ou n'est pas un locataire.",
        ];
    }
}
