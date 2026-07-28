<?php

namespace App\Http\Requests\Message;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Message::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'content' => ['required', 'string'],
            'lease_id' => ['nullable', 'integer', 'exists:leases,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
        ];
    }
}
