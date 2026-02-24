<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryAdjustmentController extends Controller
{
    protected $adjustmentService;

    public function __construct(InventoryAdjustmentService $adjustmentService)
    {
        $this->adjustmentService = $adjustmentService;
    }

    /**
     * Get all inventory adjustments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['inventory_item_id', 'adjustment_type', 'status']);
            $perPage = $request->input('per_page', 15);

            $adjustments = $this->adjustmentService->getAllAdjustments($filters, $perPage);

            return response()->json($adjustments);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get adjustment by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->getAdjustmentById($id);

            if (!$adjustment) {
                return response()->json(['message' => 'Inventory adjustment not found.'], 404);
            }

            return response()->json(['data' => $adjustment]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new inventory adjustment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->createAdjustment($request->all());

            return response()->json([
                'message' => 'Inventory adjustment created successfully.',
                'data' => $adjustment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update inventory adjustment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->updateAdjustment($id, $request->all());

            return response()->json([
                'message' => 'Inventory adjustment updated successfully.',
                'data' => $adjustment,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete inventory adjustment
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->adjustmentService->deleteAdjustment($id);

            return response()->json(['message' => 'Inventory adjustment deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve inventory adjustment
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $approvedBy = $request->input('approved_by', $request->user()->id);
            $adjustment = $this->adjustmentService->approveAdjustment($id, $approvedBy);

            return response()->json([
                'message' => 'Inventory adjustment approved successfully. Stock card entry created.',
                'data' => $adjustment,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject inventory adjustment
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $rejectedBy = $request->input('rejected_by');
            $reason = $request->input('reason', '');

            $adjustment = $this->adjustmentService->rejectAdjustment($id, $rejectedBy, $reason);

            return response()->json([
                'message' => 'Inventory adjustment rejected.',
                'data' => $adjustment,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending adjustments
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $adjustments = $this->adjustmentService->getPendingAdjustments($perPage);

            return response()->json($adjustments);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
