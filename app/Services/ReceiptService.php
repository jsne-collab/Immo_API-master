<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\User;
use App\Repositories\ReceiptRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ReceiptService
{
    public function __construct(private readonly ReceiptRepository $receipts) {}

    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->receipts->paginateForUser($user, $perPage);
    }

    public function show(int $id): Receipt
    {
        return $this->receipts->findOrFail($id);
    }
}
