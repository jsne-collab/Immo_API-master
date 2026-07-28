<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Règle A.6.4 : génère automatiquement une quittance PDF, mais
 * uniquement pour un paiement au statut "validated". Dispatché par
 * PaymentService dès qu'un paiement passe (ou est créé) à ce statut.
 */
class GenerateReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PDF_DISK = 'public';

    public function __construct(private readonly Payment $payment) {}

    public function handle(): void
    {
        $payment = $this->payment->fresh();

        if (! $payment || ! $payment->isValidated()) {
            return;
        }

        if (Receipt::where('payment_id', $payment->id)->exists()) {
            return;
        }

        $payment->loadMissing(['lease.property', 'tenant', 'lease.owner']);

        $receiptNumber = 'QUIT-'.$payment->created_at->format('Y').'-'.str_pad(
            (string) $payment->id,
            6,
            '0',
            STR_PAD_LEFT
        );

        $pdf = Pdf::loadView('pdf.receipt', [
            'receiptNumber' => $receiptNumber,
            'payment' => $payment,
            'property' => $payment->lease->property,
            'tenant' => $payment->tenant,
            'owner' => $payment->lease->owner,
        ]);

        $path = 'receipts/'.$receiptNumber.'.pdf';
        Storage::disk(self::PDF_DISK)->put($path, $pdf->output());

        Receipt::create([
            'payment_id' => $payment->id,
            'receipt_number' => $receiptNumber,
            'pdf_path' => $path,
            'generated_at' => now(),
        ]);
    }
}
