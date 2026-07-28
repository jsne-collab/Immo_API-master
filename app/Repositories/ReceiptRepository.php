<?php

namespace App\Repositories;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ReceiptRepository
{
    private const WITH = ['payment.lease.property', 'payment.tenant', 'payment.lease.owner'];

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Receipt::with(self::WITH)
            ->whereHas('payment.lease', function ($leaseQuery) use ($user) {
                $leaseQuery->where('owner_id', $user->id)->orWhere('tenant_id', $user->id);
            })
            ->latest('generated_at')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Receipt
    {
        return Receipt::with(self::WITH)->findOrFail($id);
    }
}
