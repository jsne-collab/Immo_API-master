<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly ReceiptService $receiptService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Receipt::class);

        return $this->paginated($this->receiptService->listForUser($request->user()), ReceiptResource::class);
    }

    public function show(Request $request, Receipt $receipt): JsonResponse
    {
        $this->authorize('view', $receipt);

        return $this->success(new ReceiptResource($this->receiptService->show($receipt->id)), '');
    }

    public function download(Request $request, Receipt $receipt): JsonResponse
    {
        $this->authorize('view', $receipt);

        $resource = new ReceiptResource($this->receiptService->show($receipt->id));

        return $this->success(['url' => $resource->resolve()['pdf_url']], '');
    }
}
