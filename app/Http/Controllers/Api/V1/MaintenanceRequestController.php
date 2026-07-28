<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenanceRequest\StoreMaintenanceCommentRequest;
use App\Http\Requests\MaintenanceRequest\StoreMaintenanceRequestRequest;
use App\Http\Requests\MaintenanceRequest\UpdateMaintenanceRequestRequest;
use App\Http\Requests\MaintenanceRequest\UpdateMaintenanceRequestStatusRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly MaintenanceRequestService $maintenanceRequests) {}

    public function index(Request $request): JsonResponse
    {
        $requests = $this->maintenanceRequests->listForUser($request->user(), $this->filters($request));

        return $this->paginated($requests, MaintenanceRequestResource::class);
    }

    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $maintenanceRequest = $this->maintenanceRequests->create($request->user(), array_merge($request->validated(), [
            'photo' => $request->file('photo'),
        ]));

        return $this->success(new MaintenanceRequestResource($maintenanceRequest), 'Demande de maintenance créée avec succès.', 201);
    }

    public function show(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $this->authorize('view', $maintenanceRequest);

        return $this->success(new MaintenanceRequestResource($this->maintenanceRequests->show($maintenanceRequest->id)), '');
    }

    public function update(UpdateMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $updated = $this->maintenanceRequests->update($maintenanceRequest, array_merge($request->validated(), [
            'photo' => $request->file('photo'),
        ]));

        return $this->success(new MaintenanceRequestResource($updated), 'Demande de maintenance mise à jour avec succès.');
    }

    public function destroy(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $this->authorize('delete', $maintenanceRequest);

        $this->maintenanceRequests->delete($maintenanceRequest);

        return $this->success(null, 'Demande de maintenance supprimée avec succès.');
    }

    public function updateStatus(UpdateMaintenanceRequestStatusRequest $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $updated = $this->maintenanceRequests->updateStatus($maintenanceRequest, $request->validated()['status']);

        return $this->success(new MaintenanceRequestResource($updated), 'Statut mis à jour avec succès.');
    }

    public function storeComment(StoreMaintenanceCommentRequest $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $updated = $this->maintenanceRequests->addComment($maintenanceRequest, $request->user(), $request->validated()['comment']);

        return $this->success(new MaintenanceRequestResource($updated), 'Commentaire ajouté avec succès.', 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->only(['status', 'priority', 'property_id']);
    }
}
