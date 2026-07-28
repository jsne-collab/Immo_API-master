<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Property;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly PaymentRepository $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForOwner(User $owner, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->expenses->paginateForOwner($owner, $filters, $perPage);
    }

    public function show(int $id): Expense
    {
        return $this->expenses->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Expense
    {
        $property = Property::findOrFail($data['property_id']);

        if ($property->owner_id !== $owner->id) {
            throw ValidationException::withMessages([
                'property_id' => ['Ce bien ne vous appartient pas.'],
            ]);
        }

        return $this->expenses->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'category' => $data['category'] ?? Expense::CATEGORY_OTHER,
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data): Expense
    {
        return $this->expenses->update($expense, $data);
    }

    public function delete(Expense $expense): void
    {
        $this->expenses->delete($expense);
    }

    /**
     * Solde net d'un bien : revenus des paiements validés moins les
     * charges enregistrées.
     *
     * @return array<string, float>
     */
    public function netBalanceForProperty(Property $property): array
    {
        $revenue = $this->payments->totalValidatedForProperty($property->id);
        $expenses = $this->expenses->totalForProperty($property->id);

        return [
            'total_revenue' => $revenue,
            'total_expenses' => $expenses,
            'net_balance' => $revenue - $expenses,
        ];
    }
}
