<?php

namespace App\Http\Requests\PropertyUnit;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('unit'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unit_name' => ['sometimes', 'string', 'max:255'],
            'floor' => ['sometimes', 'nullable', 'string', 'max:50'],
            'rooms_count' => ['sometimes', 'integer', 'min:1'],
            'monthly_rent' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:available,rented,maintenance'],
        ];
    }
}
