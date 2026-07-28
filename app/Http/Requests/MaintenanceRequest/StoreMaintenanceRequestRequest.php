<?php

namespace App\Http\Requests\MaintenanceRequest;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MaintenanceRequest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['sometimes', 'in:low,medium,high,urgent'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
