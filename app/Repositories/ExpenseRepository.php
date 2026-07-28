<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseRepository
{
    private const WITH = ['property'];

    /**
     * @param  array<string, mixed>  $filters  ['property_id' => ..., 'category' => ..., 'from' => ..., 'to' => ...]
     */
    public function paginateForOwner(User $owner, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Expense::with(self::WITH)->where('owner_id', $owner->id);

        $this->applyFilters($query, $filters);

        return $query->latest('expense_date')->paginate($perPage);
    }

    public function findOrFail(int $id): Expense
    {
        return Expense::with(self::WITH)->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense->fresh(self::WITH);
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    public function totalForProperty(int $propertyId): float
    {
        return (float) Expense::where('property_id', $propertyId)->sum('amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('expense_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('expense_date', '<=', $filters['to']);
        }
    }
}
