<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financial\AddLiquidationItemRequest;
use App\Http\Requests\Financial\StoreLiquidationRequest;
use App\Http\Requests\Financial\UpdateLiquidationRequest;
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
        $filters = $request->only(['cash_advance_id', 'employee_id', 'status', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 15);

        $liquidations = $this->liquidationService->getAllLiquidations($filters, $perPage);

        return response()->json($liquidations);
    }

    /**
     * Get liquidation by ID
     */
    public function show(int $id): JsonResponse
    {
        $liquidation = $this->liquidationService->getLiquidationById($id);

        if (!$liquidation) {
            return response()->json(['message' => 'Liquidation not found.'], 404);
        }

        return response()->json(['data' => $liquidation]);
    }

    /**
     * Create new liquidation
     */
    public function store(StoreLiquidationRequest $request): JsonResponse
    {
        try {
            $liquidation = $this->liquidationService->createLiquidation($request->validated());

            return response()->json([
                'message' => 'Liquidation created successfully.',
                'data'    => $liquidation,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update liquidation
     */
    public function update(UpdateLiquidationRequest $request, int $id): JsonResponse
    {
        try {
            $liquidation = $this->liquidationService->updateLiquidation($id, $request->validated());

            return response()->json([
                'message' => 'Liquidation updated successfully.',
                'data'    => $liquidation,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
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
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Add item to liquidation
     */
    public function addItem(AddLiquidationItemRequest $request, int $id): JsonResponse
    {
        try {
            $item = $this->liquidationService->addLiquidationItem($id, $request->validated());

            return response()->json([
                'message' => 'Liquidation item added successfully.',
                'data'    => $item,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve liquidation
     * The authenticated user is always recorded as the approver.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $liquidation = $this->liquidationService->approveLiquidation($id, $request->user()->id);

            return response()->json([
                'message' => 'Liquidation approved successfully. Cash advance status updated.',
                'data'    => $liquidation,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reject liquidation
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $liquidation = $this->liquidationService->rejectLiquidation(
                $id,
                $request->user()->id,
                $validated['reason']
            );

            return response()->json([
                'message' => 'Liquidation rejected.',
                'data'    => $liquidation,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get pending liquidations
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $liquidations = $this->liquidationService->getPendingLiquidations($perPage);

        return response()->json($liquidations);
    }

    /**
     * Get liquidations by cash advance
     */
    public function byCashAdvance(int $caId): JsonResponse
    {
        $liquidations = $this->liquidationService->getLiquidationsByCashAdvance($caId);

        return response()->json(['data' => $liquidations]);
    }
}
