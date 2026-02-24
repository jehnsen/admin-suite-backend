<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Inventory\RejectInventoryAdjustmentRequest;
use App\Http\Requests\Inventory\StoreInventoryAdjustmentRequest;
use App\Http\Requests\Inventory\UpdateInventoryAdjustmentRequest;

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
        $filters = $request->only(['inventory_item_id', 'adjustment_type', 'status']);
        $perPage = $request->input('per_page', 15);

        $adjustments = $this->adjustmentService->getAllAdjustments($filters, $perPage);

        return response()->json($adjustments);
    }

    /**
     * Get adjustment by ID
     */
    public function show(int $id): JsonResponse
    {
        $adjustment = $this->adjustmentService->getAdjustmentById($id);

        if (!$adjustment) {
            return response()->json(['message' => 'Inventory adjustment not found.'], 404);
        }

        return response()->json(['data' => $adjustment]);
    }

    /**
     * Create new inventory adjustment
     */
    public function store(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->createAdjustment($request->validated());

            return response()->json([
                'message' => 'Inventory adjustment created successfully.',
                'data'    => $adjustment,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update inventory adjustment
     */
    public function update(UpdateInventoryAdjustmentRequest $request, int $id): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->updateAdjustment($id, $request->validated());

            return response()->json([
                'message' => 'Inventory adjustment updated successfully.',
                'data'    => $adjustment,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
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
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve inventory adjustment
     * The authenticated user is always recorded as the approver.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->approveAdjustment($id, $request->user()->id);

            return response()->json([
                'message' => 'Inventory adjustment approved successfully. Stock card entry created.',
                'data'    => $adjustment,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reject inventory adjustment
     */
    public function reject(RejectInventoryAdjustmentRequest $request, int $id): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->rejectAdjustment(
                $id,
                $request->user()->id,
                $request->input('reason')
            );

            return response()->json([
                'message' => 'Inventory adjustment rejected.',
                'data'    => $adjustment,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get pending adjustments
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $adjustments = $this->adjustmentService->getPendingAdjustments($perPage);

        return response()->json($adjustments);
    }
}
