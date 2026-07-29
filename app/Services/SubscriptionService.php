<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\PaymentMethod;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

/**
 * Abonnement plateforme (droits d'utilisation) payé par les propriétaires —
 * distinct des loyers ([PaymentService]), qui vont du locataire au
 * propriétaire.
 */
class SubscriptionService
{
    /**
     * Statut courant de l'abonnement d'un propriétaire, dérivé de son
     * dernier enregistrement : jamais payé, en attente de validation admin,
     * à jour, ou en retard (période expirée sans renouvellement).
     *
     * @return array{status: string, next_due_date: ?string, current: ?Subscription}
     */
    public function statusFor(User $owner): array
    {
        $latest = Subscription::where('owner_id', $owner->id)->latest('period_end')->first();

        if (! $latest) {
            return ['status' => 'never', 'next_due_date' => null, 'current' => null];
        }

        $status = match (true) {
            $latest->status === Subscription::STATUS_PENDING => 'pending',
            $latest->period_end->isFuture() || $latest->period_end->isToday() => 'paid',
            default => 'overdue',
        };

        return [
            'status' => $status,
            'next_due_date' => $latest->period_end->toDateString(),
            'current' => $latest,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function initiate(User $owner, array $data): Subscription
    {
        $latest = Subscription::where('owner_id', $owner->id)->latest('period_end')->first();

        $start = $latest && $latest->period_end->isFuture()
            ? Carbon::parse($latest->period_end)->addDay()
            : now();

        $paymentMethodId = $data['payment_method_id'] ?? null;

        if ($paymentMethodId === null && ! empty($data['method_type'])) {
            $paymentMethodId = $this->upsertPaymentMethod($owner, $data)->id;
        }

        return Subscription::create([
            'owner_id' => $owner->id,
            'amount' => config('subscription.amount'),
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->addMonths((int) config('subscription.period_months'))->toDateString(),
            'payment_method_id' => $paymentMethodId,
            'status' => Subscription::STATUS_PENDING,
            'reference' => $data['reference'] ?? null,
        ]);
    }

    public function validate(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => Subscription::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Vue d'ensemble admin : tous les propriétaires (avec ou sans locataire
     * actif) et le statut de leur abonnement plateforme.
     *
     * @return array<int, array<string, mixed>>
     */
    public function adminOverview(): array
    {
        $owners = User::where('role', User::ROLE_OWNER)->orderBy('name')->get();
        $ownerIds = $owners->pluck('id');

        $propertyCounts = Property::whereIn('owner_id', $ownerIds)
            ->selectRaw('owner_id, count(*) as aggregate')
            ->groupBy('owner_id')
            ->pluck('aggregate', 'owner_id');

        $tenantCounts = Lease::whereIn('owner_id', $ownerIds)
            ->where('status', Lease::STATUS_ACTIVE)
            ->distinct()
            ->get(['owner_id', 'tenant_id'])
            ->groupBy('owner_id')
            ->map(fn ($leases) => $leases->pluck('tenant_id')->unique()->count());

        return $owners->map(function (User $owner) use ($propertyCounts, $tenantCounts) {
            $subscription = $this->statusFor($owner);

            return [
                'owner' => $owner,
                'properties_count' => $propertyCounts[$owner->id] ?? 0,
                'tenant_count' => $tenantCounts[$owner->id] ?? 0,
                'subscription_status' => $subscription['status'],
                'next_due_date' => $subscription['next_due_date'],
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertPaymentMethod(User $owner, array $data): PaymentMethod
    {
        return PaymentMethod::create([
            'user_id' => $owner->id,
            'type' => $data['method_type'],
            'provider' => $data['method_provider'] ?? null,
            'account_number' => $data['method_account_number'] ?? null,
            'is_default' => ! PaymentMethod::where('user_id', $owner->id)->exists(),
        ]);
    }
}
