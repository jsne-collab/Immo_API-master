<?php

namespace App\Models;

use Database\Factories\PropertyUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyUnit extends Model
{
    /** @use HasFactory<PropertyUnitFactory> */
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_RENTED = 'rented';

    public const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'property_id',
        'unit_name',
        'floor',
        'rooms_count',
        'monthly_rent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_rent' => 'decimal:2',
            'rooms_count' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
