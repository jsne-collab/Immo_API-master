<?php

namespace App\Services;

use App\Events\DashboardCacheShouldFlush;
use App\Models\ActivityLog;
use App\Models\Lease;
use App\Models\LeaseDocument;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\LeaseRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeaseService
{
    private const PDF_DISK = 'public';

    public function __construct(
        private readonly LeaseRepository $leases,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->leases->paginateForUser($user, $perPage);
    }

    public function show(int $id): Lease
    {
        return $this->leases->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Lease
    {
        $property = Property::findOrFail($data['property_id']);
        $unitId = $data['unit_id'] ?? null;

        // Règle A.6.1 : un seul bail actif à la fois par bien/unité.
        if ($this->leases->hasActiveLease($property->id, $unitId)) {
            throw ValidationException::withMessages([
                'property_id' => ['Ce bien (ou cette unité) a déjà un bail actif en cours.'],
            ]);
        }

        $lease = $this->leases->create([
            'property_id' => $property->id,
            'unit_id' => $unitId,
            'tenant_id' => $data['tenant_id'],
            'owner_id' => $owner->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'monthly_rent' => $data['monthly_rent'],
            // Règle A.6.9 : dépôt de garantie obligatoire, conservé dans l'historique du bail.
            'deposit_amount' => $data['deposit_amount'],
            'status' => Lease::STATUS_ACTIVE,
        ]);

        $this->applyRentedStatus($property, $unitId);
        $this->upsertTenantProfile($data);
        $this->generateContractPdf($lease);
        DashboardCacheShouldFlush::dispatch($owner->id);
        $this->activityLog->log($owner, ActivityLog::ACTION_LEASE_CREATED, "Bail créé pour {$property->title}.");

        return $lease->fresh(['property', 'unit', 'tenant', 'owner']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lease $lease, array $data): Lease
    {
        return $this->leases->update($lease, $data);
    }

    public function terminate(Lease $lease, ?string $terminationDate): Lease
    {
        if ($lease->status !== Lease::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'status' => ['Ce bail ne peut pas être résilié depuis son statut actuel.'],
            ]);
        }

        $endDate = $terminationDate ?? now()->toDateString();

        $lease = $this->leases->update($lease, [
            'status' => Lease::STATUS_TERMINATED,
            'end_date' => $endDate < $lease->end_date->toDateString() ? $endDate : $lease->end_date,
        ]);

        $this->applyAvailableStatus($lease);
        DashboardCacheShouldFlush::dispatch($lease->owner_id);
        $this->activityLog->log($lease->owner, ActivityLog::ACTION_LEASE_TERMINATED, "Bail #{$lease->id} résilié.");

        return $lease;
    }

    public function renew(Lease $lease, string $newEndDate): Lease
    {
        if (! in_array($lease->status, [Lease::STATUS_ACTIVE, Lease::STATUS_EXPIRED], true)) {
            throw ValidationException::withMessages([
                'status' => ['Ce bail ne peut pas être renouvelé depuis son statut actuel.'],
            ]);
        }

        if ($newEndDate <= $lease->end_date->toDateString()) {
            throw ValidationException::withMessages([
                'end_date' => ['La nouvelle date de fin doit être postérieure à la date de fin actuelle.'],
            ]);
        }

        $wasExpired = $lease->status === Lease::STATUS_EXPIRED;

        if ($wasExpired && $this->leases->hasActiveLease($lease->property_id, $lease->unit_id, $lease->id)) {
            throw ValidationException::withMessages([
                'property_id' => ['Ce bien (ou cette unité) a déjà un autre bail actif en cours.'],
            ]);
        }

        $lease = $this->leases->update($lease, [
            'end_date' => $newEndDate,
            'status' => Lease::STATUS_ACTIVE,
        ]);

        if ($wasExpired) {
            $this->applyRentedStatus($lease->property, $lease->unit_id);
        }

        $this->generateContractPdf($lease);
        DashboardCacheShouldFlush::dispatch($lease->owner_id);

        return $lease->fresh(['property', 'unit', 'tenant', 'owner']);
    }

    /**
     * Règle A.6.6 : un contrat passe automatiquement à "expired" à sa date
     * de fin s'il n'a pas été renouvelé. Destiné à un job planifié
     * (voir routes/console.php).
     */
    public function expireOverdueLeases(): int
    {
        $expired = 0;

        foreach ($this->leases->findExpiring() as $lease) {
            $lease = $this->leases->update($lease, ['status' => Lease::STATUS_EXPIRED]);
            $this->applyAvailableStatus($lease);
            DashboardCacheShouldFlush::dispatch($lease->owner_id);
            $expired++;
        }

        return $expired;
    }

    private function applyRentedStatus(Property $property, ?int $unitId): void
    {
        if ($unitId !== null) {
            PropertyUnit::whereKey($unitId)->update(['status' => PropertyUnit::STATUS_RENTED]);
        } else {
            $property->update(['status' => Property::STATUS_RENTED]);
        }
    }

    private function applyAvailableStatus(Lease $lease): void
    {
        if ($lease->unit_id !== null) {
            PropertyUnit::whereKey($lease->unit_id)->update(['status' => PropertyUnit::STATUS_AVAILABLE]);
        } else {
            Property::whereKey($lease->property_id)->update(['status' => Property::STATUS_AVAILABLE]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertTenantProfile(array $data): void
    {
        $guarantorFields = array_intersect_key(
            $data,
            array_flip(['guarantor_name', 'guarantor_phone', 'occupation', 'monthly_income'])
        );

        if ($guarantorFields === []) {
            return;
        }

        Tenant::updateOrCreate(['user_id' => $data['tenant_id']], $guarantorFields);
    }

    private function generateContractPdf(Lease $lease): void
    {
        $lease->loadMissing(['property', 'unit', 'tenant', 'owner']);

        $pdf = Pdf::loadView('pdf.lease-contract', [
            'lease' => $lease,
            'property' => $lease->property,
            'unit' => $lease->unit,
            'tenant' => $lease->tenant,
            'owner' => $lease->owner,
        ]);

        if ($lease->contract_pdf_path) {
            Storage::disk(self::PDF_DISK)->delete($lease->contract_pdf_path);
        }

        $path = 'leases/contract-'.$lease->id.'-'.Str::random(8).'.pdf';
        Storage::disk(self::PDF_DISK)->put($path, $pdf->output());

        $lease->update(['contract_pdf_path' => $path]);

        LeaseDocument::create([
            'lease_id' => $lease->id,
            'document_type' => LeaseDocument::TYPE_CONTRACT,
            'file_path' => $path,
            'uploaded_by' => $lease->owner_id,
        ]);
    }
}
