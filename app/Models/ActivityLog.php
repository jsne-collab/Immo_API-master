<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public const ACTION_REGISTER = 'register';

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGIN_FAILED = 'login_failed';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_PASSWORD_RESET = 'password_reset';

    public const ACTION_LEASE_CREATED = 'lease_created';

    public const ACTION_LEASE_TERMINATED = 'lease_terminated';

    public const ACTION_PAYMENT_VALIDATED = 'payment_validated';

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
