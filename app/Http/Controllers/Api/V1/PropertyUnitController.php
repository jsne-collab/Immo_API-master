<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyUnit\StorePropertyUnitRequest;
use App\Http\Requests\PropertyUnit\UpdatePropertyUnitRequest;
use App\Http\Resources\PropertyUnitResource;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Services\PropertyUnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyUnitController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly PropertyUnitService $unitService) {}

    public function index(Request $request, Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        return $this->paginated($this->unitService->listForProperty($property), PropertyUnitResource::class);
    }

    public function store(StorePropertyUnitRequest $request, Property $property): JsonResponse
    {
        $unit = $this->unitService->create($property, $request->validated());

        return $this->success(new PropertyUnitResource($unit), 'Unité créée avec succès.', 201);
    }

    public function show(Request $request, PropertyUnit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        return $this->success(new PropertyUnitResource($unit), '');
    }

    public function update(UpdatePropertyUnitRequest $request, PropertyUnit $unit): JsonResponse
    {
        $updated = $this->unitService->update($unit, $request->validated());

        return $this->success(new PropertyUnitResource($updated), 'Unité mise à jour avec succès.');
    }

    public function destroy(Request $request, PropertyUnit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        $this->unitService->delete($unit);

        return $this->success(null, 'Unité supprimée avec succès.');
    }
}
