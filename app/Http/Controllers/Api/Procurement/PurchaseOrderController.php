<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Procurement\PurchaseOrderResource;
use App\Services\Procurement\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderRequest;

class PurchaseOrderController extends Controller
{
    protected $poService;

    public function __construct(PurchaseOrderService $poService)
    {
        $this->poService = $poService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'supplier_id', 'fund_source', 'date_from', 'date_to', 'search']);
        $perPage = $this->getPerPage($request);

        $purchaseOrders = $this->poService->getAllPurchaseOrders($filters, $perPage);

        return PurchaseOrderResource::collection($purchaseOrders);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseOrder::where('uuid', $uuid)->value('id') ?? 0;
        $po = $this->poService->getPurchaseOrderById($id);

        if (!$po) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        return response()->json(['data' => new PurchaseOrderResource($po)]);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'prepared_by' => $request->user()->id,
            ]);

            $po = $this->poService->createPurchaseOrder($data);

            return response()->json([
                'message' => 'Purchase order created successfully.',
                'data'    => new PurchaseOrderResource($po),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdatePurchaseOrderRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseOrder::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $po = $this->poService->updatePurchaseOrder($id, $request->validated());

            return response()->json([
                'message' => 'Purchase order updated successfully.',
                'data'    => new PurchaseOrderResource($po),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseOrder::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->poService->deletePurchaseOrder($id);

            return response()->json(['message' => 'Purchase order deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseOrder::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $po = $this->poService->approvePurchaseOrder($id, $request->user()->id);

            return response()->json([
                'message' => 'Purchase order approved successfully.',
                'data'    => new PurchaseOrderResource($po),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function sendToSupplier(string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseOrder::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $po = $this->poService->sendToSupplier($id);

            return response()->json([
                'message' => 'Purchase order sent to supplier successfully.',
                'data'    => new PurchaseOrderResource($po),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function cancel(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseOrder::where('uuid', $uuid)->value('id') ?? 0;
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $po = $this->poService->cancelPurchaseOrder($id, $validated['reason']);

            return response()->json([
                'message' => 'Purchase order cancelled.',
                'data'    => new PurchaseOrderResource($po),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function pending(Request $request): AnonymousResourceCollection
    {
        $perPage = $this->getPerPage($request);
        $pos = $this->poService->getPendingPurchaseOrders($perPage);

        return PurchaseOrderResource::collection($pos);
    }

    public function statistics(): JsonResponse
    {
        $statistics = $this->poService->getPurchaseOrderStatistics();

        return response()->json(['data' => $statistics]);
    }
}
