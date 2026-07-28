<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentRepository
{
    private const WITH = ['lease.property', 'lease.owner', 'tenant', 'paymentMethod'];

    /**
     * @param  array<string, mixed>  $filters  ['lease_id' => ..., 'status' => ..., 'from' => ..., 'to' => ...]
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::with(self::WITH)
            ->whereHas('lease', function ($leaseQuery) use ($user) {
                $leaseQuery->where('owner_id', $user->id)->orWhere('tenant_id', $user->id);
            });

        $this->applyFilters($query, $filters);

        return $query->latest('payment_date')->paginate($perPage);
    }

    public function findOrFail(int $id): Payment
    {
        return Payment::with(self::WITH)->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);

        return $payment->fresh(self::WITH);
    }

    public function delete(Payment $payment): void
    {
        $payment->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function statsForUser(User $user, array $filters = []): array
    {
        $query = Payment::whereHas('lease', function ($leaseQuery) use ($user) {
            $leaseQuery->where('owner_id', $user->id)->orWhere('tenant_id', $user->id);
        });

        $this->applyFilters($query, $filters);

        $payments = $query->get(['amount', 'status']);

        return [
            'total_validated' => (float) $payments->where('status', 'validated')->sum('amount'),
            'total_pending' => (float) $payments->where('status', 'pending')->sum('amount'),
            'total_late' => (float) $payments->where('status', 'late')->sum('amount'),
            'total_partial' => (float) $payments->where('status', 'partial')->sum('amount'),
            'count_by_status' => [
                'pending' => $payments->where('status', 'pending')->count(),
                'validated' => $payments->where('status', 'validated')->count(),
                'late' => $payments->where('status', 'late')->count(),
                'partial' => $payments->where('status', 'partial')->count(),
            ],
        ];
    }

    public function totalValidatedForProperty(int $propertyId): float
    {
        return (float) Payment::whereHas('lease', function (Builder $leaseQuery) use ($propertyId) {
            $leaseQuery->where('property_id', $propertyId);
        })
            ->where('status', 'validated')
            ->sum('amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['lease_id'])) {
            $query->where('lease_id', $filters['lease_id']);
        }

        if (! empty($filters['property_id'])) {
            $query->whereHas('lease', function (Builder $leaseQuery) use ($filters) {
                $leaseQuery->where('property_id', $filters['property_id']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('payment_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('payment_date', '<=', $filters['to']);
        }
    }
}
