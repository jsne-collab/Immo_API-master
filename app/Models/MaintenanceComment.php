<?php

namespace App\Models;

use Database\Factories\MaintenanceCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceComment extends Model
{
    /** @use HasFactory<MaintenanceCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'maintenance_request_id',
        'user_id',
        'comment',
    ];

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
