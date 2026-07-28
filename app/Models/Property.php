<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_RENTED = 'rented';

    public const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'owner_id',
        'title',
        'type',
        'address',
        'city',
        'surface_area',
        'rooms_count',
        'monthly_rent',
        'deposit_amount',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'surface_area' => 'decimal:2',
            'monthly_rent' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'rooms_count' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
