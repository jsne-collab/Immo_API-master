<?php

namespace App\Services;

use App\Models\Property;
use App\Models\User;
use App\Repositories\PropertyRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PropertyService
{
    private const IMAGE_DISK = 'public';

    public function __construct(private readonly PropertyRepository $properties) {}

    public function listOwn(User $owner, int $perPage = 15): LengthAwarePaginator
    {
        return $this->properties->paginateForOwner($owner->id, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listAvailable(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->properties->paginateAvailable($filters, $perPage);
    }

    public function show(int $id): Property
    {
        return $this->properties->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Property
    {
        $data['owner_id'] = $owner->id;
        $data['status'] ??= Property::STATUS_AVAILABLE;

        return $this->properties->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Property $property, array $data): Property
    {
        return $this->properties->update($property, $data);
    }

    public function delete(Property $property): void
    {
        foreach ($property->images as $image) {
            Storage::disk(self::IMAGE_DISK)->delete($image->image_path);
        }

        $this->properties->delete($property);
    }

    public function uploadImage(Property $property, UploadedFile $file, bool $isPrimary): Property
    {
        $path = $file->store('properties', self::IMAGE_DISK);
        $this->properties->addImage($property, $path, $isPrimary);

        return $property->fresh('images');
    }

    public function deleteImage(Property $property, int $imageId): void
    {
        $image = $property->images()->find($imageId);

        if (! $image) {
            throw ValidationException::withMessages([
                'image' => ["Cette image n'appartient pas à ce bien."],
            ]);
        }

        Storage::disk(self::IMAGE_DISK)->delete($image->image_path);
        $image->delete();
    }
}
