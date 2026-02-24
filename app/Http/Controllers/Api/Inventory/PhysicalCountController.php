<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\PhysicalCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Inventory\StorePhysicalCountRequest;
use App\Http\Requests\Inventory\UpdatePhysicalCountRequest;

class PhysicalCountController extends Controller
{
    protected $physicalCountService;

    public function __construct(PhysicalCountService $physicalCountService)
    {
        $this->physicalCountService = $physicalCountService;
    }

    /**
     * Get all physical counts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['inventory_item_id', 'variance_type', 'date_from', 'date_to']);
            $perPage = $request->input('per_page', 15);

            $physicalCounts = $this->physicalCountService->getAllPhysicalCounts($filters, $perPage);

            return response()->json($physicalCounts);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get physical count by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $physicalCount = $this->physicalCountService->getPhysicalCountById($id);

            if (!$physicalCount) {
                return response()->json(['message' => 'Physical count record not found.'], 404);
            }

            return response()->json(['data' => $physicalCount]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new physical count
     */
    public function store(StorePhysicalCountRequest $request): JsonResponse
    {
        try {
            $physicalCount = $this->physicalCountService->createPhysicalCount($request->validated());

            return response()->json([
                'message' => 'Physical count recorded successfully.',
                'data' => $physicalCount,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update physical count
     */
    public function update(UpdatePhysicalCountRequest $request, int $id): JsonResponse
    {
        try {
            $physicalCount = $this->physicalCountService->updatePhysicalCount($id, $request->validated());

            return response()->json([
                'message' => 'Physical count updated successfully.',
                'data' => $physicalCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete physical count
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->physicalCountService->deletePhysicalCount($id);

            return response()->json(['message' => 'Physical count deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get items with variances
     */
    public function withVariances(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $physicalCounts = $this->physicalCountService->getItemsWithVariances($perPage);

            return response()->json($physicalCounts);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
