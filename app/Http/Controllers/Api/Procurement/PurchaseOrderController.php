<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        try {
            $filters = $request->only([
                'status',
                'supplier_id',
                'fund_source',
                'date_from',
                'date_to',
                'search'
            ]);
            $perPage = $request->input('per_page', 15);

            $purchaseOrders = $this->poService->getAllPurchaseOrders($filters, $perPage);

            return response()->json($purchaseOrders);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get purchase order by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $po = $this->poService->getPurchaseOrderById($id);

            if (!$po) {
                return response()->json(['message' => 'Purchase order not found.'], 404);
            }

            return response()->json(['data' => $po]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new purchase order
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['prepared_by'] = $request->user()->id;

            $po = $this->poService->createPurchaseOrder($data);

            return response()->json([
                'message' => 'Purchase order created successfully.',
                'data' => $po,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update purchase order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $po = $this->poService->updatePurchaseOrder($id, $request->all());

            return response()->json([
                'message' => 'Purchase order updated successfully.',
                'data' => $po,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve purchase order
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $po = $this->poService->approvePurchaseOrder($id, $request->user()->id);

            return response()->json([
                'message' => 'Purchase order approved successfully.',
                'data' => $po,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
                'data' => $po,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $po = $this->poService->cancelPurchaseOrder($id, $request->input('reason'));

            return response()->json([
                'message' => 'Purchase order cancelled.',
                'data' => $po,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending purchase orders
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $pos = $this->poService->getPendingPurchaseOrders($perPage);

            return response()->json($pos);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get purchase order statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->poService->getPurchaseOrderStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
