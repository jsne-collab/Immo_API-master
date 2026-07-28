<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\MaintenanceRequestRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MaintenanceRequestService
{
    private const PHOTO_DISK = 'public';

    public function __construct(
        private readonly MaintenanceRequestRepository $requests,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForUser(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->requests->paginateForUser($user, $filters, $perPage);
    }

    public function show(int $id): MaintenanceRequest
    {
        return $this->requests->findOrFail($id);
    }

    /**
     * Règle A.6.5 : un locataire ne peut soumettre une demande que pour un
     * bien où il a un contrat actif.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $tenant, array $data): MaintenanceRequest
    {
        $lease = Lease::where('tenant_id', $tenant->id)
            ->where('property_id', $data['property_id'])
            ->where('status', Lease::STATUS_ACTIVE)
            ->first();

        if (! $lease) {
            throw ValidationException::withMessages([
                'property_id' => ['Vous ne pouvez signaler un problème que pour un bien où vous avez un contrat actif.'],
            ]);
        }

        $photoPath = null;
        if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $photoPath = $data['photo']->store('maintenance', self::PHOTO_DISK);
        }

        $maintenanceRequest = $this->requests->create([
            'property_id' => $lease->property_id,
            'lease_id' => $lease->id,
            'tenant_id' => $tenant->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? MaintenanceRequest::PRIORITY_MEDIUM,
            'status' => MaintenanceRequest::STATUS_NEW,
            'photo_path' => $photoPath,
        ]);

        $this->notifications->notify(
            $lease->owner,
            Notification::TYPE_MAINTENANCE_REQUEST_CREATED,
            'Nouvelle demande de maintenance',
            "{$tenant->name} a signalé : {$data['title']}",
        );

        return $maintenanceRequest;
    }

    /**
     * Modification du contenu par le locataire auteur, uniquement tant que
     * la demande est encore "new".
     *
     * @param  array<string, mixed>  $data
     */
    public function update(MaintenanceRequest $maintenanceRequest, array $data): MaintenanceRequest
    {
        if (! $maintenanceRequest->isNew()) {
            throw ValidationException::withMessages([
                'status' => ['Seule une demande "new" peut encore être modifiée.'],
            ]);
        }

        if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
            if ($maintenanceRequest->photo_path) {
                Storage::disk(self::PHOTO_DISK)->delete($maintenanceRequest->photo_path);
            }

            $data['photo_path'] = $data['photo']->store('maintenance', self::PHOTO_DISK);
        }

        unset($data['photo']);

        return $this->requests->update($maintenanceRequest, $data);
    }

    /**
     * Changement de statut par le propriétaire du bien.
     */
    public function updateStatus(MaintenanceRequest $maintenanceRequest, string $status): MaintenanceRequest
    {
        return $this->requests->update($maintenanceRequest, ['status' => $status]);
    }

    public function delete(MaintenanceRequest $maintenanceRequest): void
    {
        if (! $maintenanceRequest->isNew()) {
            throw ValidationException::withMessages([
                'status' => ['Seule une demande "new" peut être supprimée.'],
            ]);
        }

        if ($maintenanceRequest->photo_path) {
            Storage::disk(self::PHOTO_DISK)->delete($maintenanceRequest->photo_path);
        }

        $this->requests->delete($maintenanceRequest);
    }

    public function addComment(MaintenanceRequest $maintenanceRequest, User $author, string $comment): MaintenanceRequest
    {
        $updated = $this->requests->addComment($maintenanceRequest, [
            'user_id' => $author->id,
            'comment' => $comment,
        ]);

        $recipient = $author->id === $maintenanceRequest->tenant_id
            ? $maintenanceRequest->lease->owner
            : $maintenanceRequest->tenant;

        $this->notifications->notify(
            $recipient,
            Notification::TYPE_MAINTENANCE_COMMENT,
            "Nouveau commentaire sur « {$maintenanceRequest->title} »",
            "{$author->name} : {$comment}",
        );

        return $updated;
    }
}
