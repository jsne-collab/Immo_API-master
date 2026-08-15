<?php

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Abonnement plateforme (droits d'utilisation) payé par un propriétaire —
 * à ne pas confondre avec [Payment], qui représente le loyer locataire ->
 * propriétaire.
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const PLAN_MONTHLY = 'monthly';

    public const PLAN_YEARLY = 'yearly';

    protected $fillable = [
        'owner_id',
        'amount',
        'plan',
        'period_start',
        'period_end',
        'payment_method_id',
        'status',
        'paid_at',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
