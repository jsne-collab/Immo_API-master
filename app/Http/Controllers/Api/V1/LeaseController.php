<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lease\RenewLeaseRequest;
use App\Http\Requests\Lease\StoreLeaseRequest;
use App\Http\Requests\Lease\TerminateLeaseRequest;
use App\Http\Requests\Lease\UpdateLeaseRequest;
use App\Http\Resources\LeaseResource;
use App\Models\Lease;
use App\Services\LeaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly LeaseService $leaseService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginated($this->leaseService->listForUser($request->user()), LeaseResource::class);
    }

    public function store(StoreLeaseRequest $request): JsonResponse
    {
        $lease = $this->leaseService->create($request->user(), $request->validated());

        return $this->success(new LeaseResource($lease), 'Bail créé avec succès.', 201);
    }

    public function show(Request $request, Lease $lease): JsonResponse
    {
        $this->authorize('view', $lease);

        return $this->success(new LeaseResource($this->leaseService->show($lease->id)), '');
    }

    public function update(UpdateLeaseRequest $request, Lease $lease): JsonResponse
    {
        $updated = $this->leaseService->update($lease, $request->validated());

        return $this->success(new LeaseResource($updated), 'Bail mis à jour avec succès.');
    }

    public function terminate(TerminateLeaseRequest $request, Lease $lease): JsonResponse
    {
        $updated = $this->leaseService->terminate($lease, $request->validated('termination_date'));

        return $this->success(new LeaseResource($updated), 'Bail résilié avec succès.');
    }

    public function renew(RenewLeaseRequest $request, Lease $lease): JsonResponse
    {
        $updated = $this->leaseService->renew($lease, $request->validated('end_date'));

        return $this->success(new LeaseResource($updated), 'Bail renouvelé avec succès.');
    }

    public function download(Request $request, Lease $lease): JsonResponse
    {
        $this->authorize('view', $lease);

        $resource = new LeaseResource($this->leaseService->show($lease->id));

        return $this->success(['url' => $resource->resolve()['contract_pdf_url']], '');
    }
}
