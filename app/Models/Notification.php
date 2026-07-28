<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    public const TYPE_PAYMENT_VALIDATED = 'payment_validated';

    public const TYPE_PAYMENT_REMINDER = 'payment_reminder';

    public const TYPE_NEW_MESSAGE = 'new_message';

    public const TYPE_MAINTENANCE_REQUEST_CREATED = 'maintenance_request_created';

    public const TYPE_MAINTENANCE_COMMENT = 'maintenance_comment';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
