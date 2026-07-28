<?php

namespace App\Services;

use App\Events\DashboardCacheShouldFlush;
use App\Jobs\GenerateReceiptJob;
use App\Models\ActivityLog;
use App\Models\Lease;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Repositories\PaymentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly NotificationService $notifications,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForUser(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->payments->paginateForUser($user, $filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function history(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->payments->paginateForUser($user, $filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function stats(User $user, array $filters): array
    {
        return $this->payments->statsForUser($user, $filters);
    }

    public function show(int $id): Payment
    {
        return $this->payments->findOrFail($id);
    }

    /**
     * Enregistrement manuel par le propriétaire (espèces, virement déjà
     * reçu...). Règle A.6.3 : uniquement sur un bail actif.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Payment
    {
        $lease = Lease::findOrFail($data['lease_id']);

        if ($lease->owner_id !== $owner->id) {
            throw ValidationException::withMessages([
                'lease_id' => ['Ce bail ne vous appartient pas.'],
            ]);
        }

        $this->assertLeaseActive($lease);

        $payment = $this->payments->create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'amount' => $data['amount'],
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'payment_date' => $data['payment_date'],
            'period_covered' => $data['period_covered'],
            'status' => $data['status'] ?? Payment::STATUS_VALIDATED,
            'reference' => $data['reference'] ?? null,
        ]);

        $this->dispatchReceiptGenerationIfValidated($payment);
        DashboardCacheShouldFlush::dispatch($lease->owner_id);

        return $payment;
    }

    /**
     * Paiement initié par le locataire lui-même pour son propre bail actif.
     *
     * @param  array<string, mixed>  $data
     */
    public function initiate(User $tenant, array $data): Payment
    {
        $lease = Lease::findOrFail($data['lease_id']);

        if ($lease->tenant_id !== $tenant->id) {
            throw ValidationException::withMessages([
                'lease_id' => ['Ce bail ne vous concerne pas.'],
            ]);
        }

        $this->assertLeaseActive($lease);

        $paymentMethodId = $data['payment_method_id'] ?? null;

        if ($paymentMethodId === null && ! empty($data['method_type'])) {
            $paymentMethodId = $this->upsertPaymentMethod($tenant, $data)->id;
        }

        $payment = $this->payments->create([
            'lease_id' => $lease->id,
            'tenant_id' => $tenant->id,
            'amount' => $data['amount'],
            'payment_method_id' => $paymentMethodId,
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'period_covered' => $data['period_covered'],
            'status' => Payment::STATUS_PENDING,
            'reference' => $data['reference'] ?? null,
        ]);

        DashboardCacheShouldFlush::dispatch($lease->owner_id);

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        $updated = $this->payments->update($payment, $data);

        $this->dispatchReceiptGenerationIfValidated($updated);
        DashboardCacheShouldFlush::dispatch($updated->lease->owner_id);

        return $updated;
    }

    public function delete(Payment $payment): void
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Seul un paiement "pending" peut être supprimé.'],
            ]);
        }

        $this->payments->delete($payment);
    }

    /**
     * Règle A.6.4 : la quittance est générée automatiquement (job en
     * queue) uniquement pour un paiement "validated". Le job lui-même
     * est idempotent (ne recrée pas de quittance si une existe déjà),
     * donc pas besoin de vérifier ici si le statut vient de changer.
     */
    private function dispatchReceiptGenerationIfValidated(Payment $payment): void
    {
        if ($payment->isValidated()) {
            GenerateReceiptJob::dispatch($payment);

            $this->notifications->notify(
                $payment->tenant,
                Notification::TYPE_PAYMENT_VALIDATED,
                'Paiement validé',
                "Votre paiement de {$payment->amount} FCFA pour la période {$payment->period_covered} a été validé.",
            );

            $this->activityLog->log(
                $payment->lease->owner,
                ActivityLog::ACTION_PAYMENT_VALIDATED,
                "Paiement #{$payment->id} validé ({$payment->amount} FCFA).",
            );
        }
    }

    private function assertLeaseActive(Lease $lease): void
    {
        if ($lease->status !== Lease::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'lease_id' => ['Un paiement ne peut être enregistré que pour un bail actif.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertPaymentMethod(User $tenant, array $data): PaymentMethod
    {
        return PaymentMethod::create([
            'user_id' => $tenant->id,
            'type' => $data['method_type'],
            'provider' => $data['method_provider'] ?? null,
            'account_number' => $data['method_account_number'] ?? null,
            'is_default' => ! PaymentMethod::where('user_id', $tenant->id)->exists(),
        ]);
    }
}
