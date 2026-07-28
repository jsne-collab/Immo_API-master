<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\SearchPropertiesRequest;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Requests\Property\UploadPropertyImageRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly PropertyService $propertyService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Property::class);

        return $this->paginated($this->propertyService->listOwn($request->user()), PropertyResource::class);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->propertyService->create($request->user(), $request->validated());

        return $this->success(new PropertyResource($property), 'Bien créé avec succès.', 201);
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        return $this->success(new PropertyResource($this->propertyService->show($property->id)), '');
    }

    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        $updated = $this->propertyService->update($property, $request->validated());

        return $this->success(new PropertyResource($updated), 'Bien mis à jour avec succès.');
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        $this->authorize('delete', $property);

        $this->propertyService->delete($property);

        return $this->success(null, 'Bien supprimé avec succès.');
    }

    public function uploadImage(UploadPropertyImageRequest $request, Property $property): JsonResponse
    {
        $updated = $this->propertyService->uploadImage(
            $property,
            $request->file('image'),
            $request->boolean('is_primary')
        );

        return $this->success(new PropertyResource($updated), 'Image ajoutée avec succès.', 201);
    }

    public function deleteImage(Request $request, Property $property, int $imageId): JsonResponse
    {
        $this->authorize('update', $property);

        $this->propertyService->deleteImage($property, $imageId);

        return $this->success(null, 'Image supprimée avec succès.');
    }

    public function search(SearchPropertiesRequest $request): JsonResponse
    {
        return $this->paginated(
            $this->propertyService->listAvailable($request->validated()),
            PropertyResource::class
        );
    }

    public function available(Request $request): JsonResponse
    {
        return $this->paginated($this->propertyService->listAvailable([]), PropertyResource::class);
    }
}
