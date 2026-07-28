<?php

namespace App\Http\Requests\Lease;

use Illuminate\Foundation\Http\FormRequest;

class TerminateLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lease'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'termination_date' => ['nullable', 'date'],
        ];
    }
}
