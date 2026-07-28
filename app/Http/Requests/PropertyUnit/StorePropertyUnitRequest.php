<?php

namespace App\Http\Requests\PropertyUnit;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unit_name' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:50'],
            'rooms_count' => ['required', 'integer', 'min:1'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
        ];
    }
}
