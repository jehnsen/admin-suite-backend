<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\InventoryItemResource;
use App\Services\Inventory\InventoryItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;

class InventoryItemController extends Controller
{
    protected $inventoryItemService;

    public function __construct(InventoryItemService $inventoryItemService)
    {
        $this->inventoryItemService = $inventoryItemService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only(['category', 'status', 'condition', 'fund_source', 'location']);
            $perPage = $this->getPerPage($request);

            $items = $this->inventoryItemService->getAllInventoryItems($filters, $perPage);

            return InventoryItemResource::collection($items);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\InventoryItem::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $item = $this->inventoryItemService->getInventoryItemById($id);

            if (!$item) {
                return response()->json(['message' => 'Inventory item not found.'], 404);
            }

            return response()->json(['data' => new InventoryItemResource($item)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function showWithBalance(string $uuid): JsonResponse
    {
        $id = \App\Models\InventoryItem::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $data = $this->inventoryItemService->getInventoryItemWithBalance($id);

            if (!$data) {
                return response()->json(['message' => 'Inventory item not found.'], 404);
            }

            return response()->json(['data' => $data]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        try {
            $item = $this->inventoryItemService->createInventoryItem($request->validated());

            return response()->json([
                'message' => 'Inventory item created successfully.',
                'data'    => new InventoryItemResource($item),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateInventoryItemRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\InventoryItem::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $item = $this->inventoryItemService->updateInventoryItem($id, $request->validated());

            return response()->json([
                'message' => 'Inventory item updated successfully.',
                'data'    => new InventoryItemResource($item),
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
        $id = \App\Models\InventoryItem::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $deleted = $this->inventoryItemService->deleteInventoryItem($id);

            if (!$deleted) {
                return response()->json(['message' => 'Inventory item not found.'], 404);
            }

            return response()->json(['message' => 'Inventory item deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        try {
            $searchTerm = $request->input('q', '');
            $perPage = $this->getPerPage($request);

            if (empty($searchTerm)) {
                abort(400, 'Search term is required.');
            }

            $items = $this->inventoryItemService->searchInventoryItems($searchTerm, $perPage);

            return InventoryItemResource::collection($items);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function withBalances(): JsonResponse
    {
        try {
            $items = $this->inventoryItemService->getInventoryItemsWithCurrentBalance();

            return response()->json(['data' => $items]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function lowStock(Request $request): JsonResponse
    {
        try {
            $threshold = $request->input('threshold', 10);
            $items = $this->inventoryItemService->getLowStockItems($threshold);

            return response()->json(['data' => $items]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->inventoryItemService->getInventoryStatistics();

            return response()->json(['data' => $stats]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
