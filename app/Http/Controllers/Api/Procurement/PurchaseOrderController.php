<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderRequest;

class PurchaseOrderController extends Controller
{
    protected $poService;

    public function __construct(PurchaseOrderService $poService)
    {
        $this->poService = $poService;
    }

    /**
     * Get all purchase orders
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'supplier_id', 'fund_source', 'date_from', 'date_to', 'search']);
        $perPage = $request->input('per_page', 15);

        $purchaseOrders = $this->poService->getAllPurchaseOrders($filters, $perPage);

        return response()->json($purchaseOrders);
    }

    /**
     * Get purchase order by ID
     */
    public function show(int $id): JsonResponse
    {
        $po = $this->poService->getPurchaseOrderById($id);

        if (!$po) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        return response()->json(['data' => $po]);
    }

    /**
     * Create new purchase order
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'prepared_by' => $request->user()->id,
            ]);

            $po = $this->poService->createPurchaseOrder($data);

            return response()->json([
                'message' => 'Purchase order created successfully.',
                'data'    => $po,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update purchase order
     */
    public function update(UpdatePurchaseOrderRequest $request, int $id): JsonResponse
    {
        try {
            $po = $this->poService->updatePurchaseOrder($id, $request->validated());

            return response()->json([
                'message' => 'Purchase order updated successfully.',
                'data'    => $po,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete purchase order
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->poService->deletePurchaseOrder($id);

            return response()->json(['message' => 'Purchase order deleted successfully.']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve purchase order
     * The authenticated user is always recorded as the approver.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $po = $this->poService->approvePurchaseOrder($id, $request->user()->id);

            return response()->json([
                'message' => 'Purchase order approved successfully.',
                'data'    => $po,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Send PO to supplier
     */
    public function sendToSupplier(int $id): JsonResponse
    {
        try {
            $po = $this->poService->sendToSupplier($id);

            return response()->json([
                'message' => 'Purchase order sent to supplier successfully.',
                'data'    => $po,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $po = $this->poService->cancelPurchaseOrder($id, $validated['reason']);

            return response()->json([
                'message' => 'Purchase order cancelled.',
                'data'    => $po,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get pending purchase orders
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $pos = $this->poService->getPendingPurchaseOrders($perPage);

        return response()->json($pos);
    }

    /**
     * Get purchase order statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->poService->getPurchaseOrderStatistics();

        return response()->json(['data' => $statistics]);
    }
}
