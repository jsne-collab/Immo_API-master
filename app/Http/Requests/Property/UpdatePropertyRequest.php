<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:maison,appartement,studio,chambre'],
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'surface_area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'rooms_count' => ['sometimes', 'integer', 'min:1'],
            'monthly_rent' => ['sometimes', 'numeric', 'min:0'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:available,rented,maintenance'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
