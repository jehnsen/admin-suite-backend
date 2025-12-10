<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    protected $inventoryItemService;

    public function __construct(InventoryItemService $inventoryItemService)
    {
        $this->inventoryItemService = $inventoryItemService;
    }

    /**
     * Get all inventory items
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['category', 'status', 'condition', 'fund_source', 'location']);
            $perPage = $request->input('per_page', 15);

            $items = $this->inventoryItemService->getAllInventoryItems($filters, $perPage);

            return response()->json($items);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory item by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $item = $this->inventoryItemService->getInventoryItemById($id);

            if (!$item) {
                return response()->json(['message' => 'Inventory item not found.'], 404);
            }

            return response()->json(['data' => $item]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory item with current stock balance
     */
    public function showWithBalance(int $id): JsonResponse
    {
        try {
            $data = $this->inventoryItemService->getInventoryItemWithBalance($id);

            if (!$data) {
                return response()->json(['message' => 'Inventory item not found.'], 404);
            }

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new inventory item
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $item = $this->inventoryItemService->createInventoryItem($request->all());

            return response()->json([
                'message' => 'Inventory item created successfully.',
                'data' => $item,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update inventory item
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = $this->inventoryItemService->updateInventoryItem($id, $request->all());

            return response()->json([
                'message' => 'Inventory item updated successfully.',
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete inventory item
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->inventoryItemService->deleteInventoryItem($id);

            if (!$deleted) {
                return response()->json(['message' => 'Inventory item not found.'], 404);
            }

            return response()->json(['message' => 'Inventory item deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search inventory items
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->input('q', '');
            $perPage = $request->input('per_page', 15);

            if (empty($searchTerm)) {
                return response()->json(['message' => 'Search term is required.'], 400);
            }

            $items = $this->inventoryItemService->searchInventoryItems($searchTerm, $perPage);

            return response()->json($items);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all inventory items with current stock balances
     */
    public function withBalances(): JsonResponse
    {
        try {
            $items = $this->inventoryItemService->getInventoryItemsWithCurrentBalance();

            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get low stock items
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $threshold = $request->input('threshold', 10);
            $items = $this->inventoryItemService->getLowStockItems($threshold);

            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get inventory statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->inventoryItemService->getInventoryStatistics();

            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
