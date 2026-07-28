<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly PaymentService $paymentService) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->listForUser($request->user(), $this->filters($request));

        return $this->paginated($payments, PaymentResource::class);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->create($request->user(), $request->validated());

        return $this->success(new PaymentResource($payment), 'Paiement enregistré avec succès.', 201);
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->initiate($request->user(), $request->validated());

        return $this->success(new PaymentResource($payment), 'Paiement initié, en attente de validation.', 201);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return $this->success(new PaymentResource($this->paymentService->show($payment->id)), '');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $updated = $this->paymentService->update($payment, $request->validated());

        return $this->success(new PaymentResource($updated), 'Paiement mis à jour avec succès.');
    }

    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        $this->paymentService->delete($payment);

        return $this->success(null, 'Paiement supprimé avec succès.');
    }

    public function history(Request $request): JsonResponse
    {
        $payments = $this->paymentService->history($request->user(), $this->filters($request));

        return $this->paginated($payments, PaymentResource::class);
    }

    public function stats(Request $request): JsonResponse
    {
        return $this->success($this->paymentService->stats($request->user(), $this->filters($request)), '');
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->only(['lease_id', 'property_id', 'status', 'from', 'to']);
    }
}
