<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'receipt_number' => 'QUIT-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'pdf_path' => 'receipts/'.fake()->uuid().'.pdf',
            'generated_at' => now(),
        ];
    }
}
