<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\InventoryAdjustmentResource;
use App\Services\Inventory\InventoryAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['inventory_item_id', 'adjustment_type', 'status']);
        $perPage = $this->getPerPage($request);

        $adjustments = $this->adjustmentService->getAllAdjustments($filters, $perPage);

        return InventoryAdjustmentResource::collection($adjustments);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\InventoryAdjustment::where('uuid', $uuid)->value('id') ?? 0;
        $adjustment = $this->adjustmentService->getAdjustmentById($id);

        if (!$adjustment) {
            return response()->json(['message' => 'Inventory adjustment not found.'], 404);
        }

        return response()->json(['data' => new InventoryAdjustmentResource($adjustment)]);
    }

    public function store(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        try {
            $adjustment = $this->adjustmentService->createAdjustment($request->validated());

            return response()->json([
                'message' => 'Inventory adjustment created successfully.',
                'data'    => new InventoryAdjustmentResource($adjustment),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateInventoryAdjustmentRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\InventoryAdjustment::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $adjustment = $this->adjustmentService->updateAdjustment($id, $request->validated());

            return response()->json([
                'message' => 'Inventory adjustment updated successfully.',
                'data'    => new InventoryAdjustmentResource($adjustment),
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
        $id = \App\Models\InventoryAdjustment::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->adjustmentService->deleteAdjustment($id);

            return response()->json(['message' => 'Inventory adjustment deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\InventoryAdjustment::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $adjustment = $this->adjustmentService->approveAdjustment($id, $request->user()->id);

            return response()->json([
                'message' => 'Inventory adjustment approved successfully. Stock card entry created.',
                'data'    => new InventoryAdjustmentResource($adjustment),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function reject(RejectInventoryAdjustmentRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\InventoryAdjustment::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $adjustment = $this->adjustmentService->rejectAdjustment(
                $id,
                $request->user()->id,
                $request->input('reason')
            );

            return response()->json([
                'message' => 'Inventory adjustment rejected.',
                'data'    => new InventoryAdjustmentResource($adjustment),
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
        $adjustments = $this->adjustmentService->getPendingAdjustments($perPage);

        return InventoryAdjustmentResource::collection($adjustments);
    }
}
