<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\LeaseDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaseDocument>
 */
class LeaseDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lease_id' => Lease::factory(),
            'document_type' => LeaseDocument::TYPE_CONTRACT,
            'file_path' => 'leases/'.fake()->uuid().'.pdf',
            'uploaded_by' => User::factory()->owner(),
        ];
    }
}
