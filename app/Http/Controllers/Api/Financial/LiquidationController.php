<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Services\Financial\LiquidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiquidationController extends Controller
{
    protected $liquidationService;

    public function __construct(LiquidationService $liquidationService)
    {
        $this->liquidationService = $liquidationService;
    }

    /**
     * Get all liquidations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['cash_advance_id', 'employee_id', 'status', 'date_from', 'date_to']);
            $perPage = $request->input('per_page', 15);

            $liquidations = $this->liquidationService->getAllLiquidations($filters, $perPage);

            return response()->json($liquidations);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get liquidation by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $liquidation = $this->liquidationService->getLiquidationById($id);

            if (!$liquidation) {
                return response()->json(['message' => 'Liquidation not found.'], 404);
            }

            return response()->json(['data' => $liquidation]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new liquidation
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $liquidation = $this->liquidationService->createLiquidation($request->all());

            return response()->json([
                'message' => 'Liquidation created successfully.',
                'data' => $liquidation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update liquidation
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $liquidation = $this->liquidationService->updateLiquidation($id, $request->all());

            return response()->json([
                'message' => 'Liquidation updated successfully.',
                'data' => $liquidation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete liquidation
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->liquidationService->deleteLiquidation($id);

            return response()->json(['message' => 'Liquidation deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Add item to liquidation
     */
    public function addItem(Request $request, int $id): JsonResponse
    {
        try {
            $item = $this->liquidationService->addLiquidationItem($id, $request->all());

            return response()->json([
                'message' => 'Liquidation item added successfully.',
                'data' => $item,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve liquidation
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $approvedBy = $request->input('approved_by');
            $liquidation = $this->liquidationService->approveLiquidation($id, $approvedBy);

            return response()->json([
                'message' => 'Liquidation approved successfully. Cash advance status updated.',
                'data' => $liquidation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject liquidation
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $rejectedBy = $request->input('rejected_by');
            $reason = $request->input('reason', '');

            $liquidation = $this->liquidationService->rejectLiquidation($id, $rejectedBy, $reason);

            return response()->json([
                'message' => 'Liquidation rejected.',
                'data' => $liquidation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending liquidations
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $liquidations = $this->liquidationService->getPendingLiquidations($perPage);

            return response()->json($liquidations);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get liquidations by cash advance
     */
    public function byCashAdvance(int $caId): JsonResponse
    {
        try {
            $liquidations = $this->liquidationService->getLiquidationsByCashAdvance($caId);

            return response()->json(['data' => $liquidations]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
