<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ExpenseService $expenseService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = $this->expenseService->listForOwner($request->user(), $this->filters($request));

        return $this->paginated($expenses, ExpenseResource::class);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenseService->create($request->user(), $request->validated());

        return $this->success(new ExpenseResource($expense), 'Charge enregistrée avec succès.', 201);
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        return $this->success(new ExpenseResource($this->expenseService->show($expense->id)), '');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $updated = $this->expenseService->update($expense, $request->validated());

        return $this->success(new ExpenseResource($updated), 'Charge mise à jour avec succès.');
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        $this->expenseService->delete($expense);

        return $this->success(null, 'Charge supprimée avec succès.');
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->only(['property_id', 'category', 'from', 'to']);
    }
}
