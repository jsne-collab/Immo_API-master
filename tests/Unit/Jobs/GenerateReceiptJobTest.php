<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateReceiptJob;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateReceiptJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_receipt_for_a_validated_payment(): void
    {
        Storage::fake('public');
        $payment = Payment::factory()->create(['status' => Payment::STATUS_VALIDATED]);

        (new GenerateReceiptJob($payment))->handle();

        $this->assertDatabaseHas('receipts', ['payment_id' => $payment->id]);
        $receipt = Receipt::where('payment_id', $payment->id)->firstOrFail();
        Storage::disk('public')->assertExists($receipt->pdf_path);
        $this->assertStringStartsWith('QUIT-', $receipt->receipt_number);
    }

    public function test_it_does_nothing_for_a_non_validated_payment(): void
    {
        Storage::fake('public');
        $payment = Payment::factory()->pending()->create();

        (new GenerateReceiptJob($payment))->handle();

        $this->assertDatabaseMissing('receipts', ['payment_id' => $payment->id]);
    }

    public function test_it_does_not_duplicate_a_receipt_if_one_already_exists(): void
    {
        Storage::fake('public');
        $payment = Payment::factory()->create(['status' => Payment::STATUS_VALIDATED]);
        Receipt::factory()->create(['payment_id' => $payment->id]);

        (new GenerateReceiptJob($payment))->handle();

        $this->assertDatabaseCount('receipts', 1);
    }

    public function test_it_reflects_the_payments_latest_status_not_a_stale_one(): void
    {
        Storage::fake('public');
        $payment = Payment::factory()->create(['status' => Payment::STATUS_VALIDATED]);
        $job = new GenerateReceiptJob($payment);

        // Le paiement est repassé "pending" après la mise en file du job.
        $payment->update(['status' => Payment::STATUS_PENDING]);

        $job->handle();

        $this->assertDatabaseMissing('receipts', ['payment_id' => $payment->id]);
    }
}
