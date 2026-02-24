<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Inventory\StockInRequest;
use App\Http\Requests\Inventory\StockOutRequest;
use App\Http\Requests\Inventory\RecordDonationRequest;

class StockCardController extends Controller
{
    protected $stockCardService;

    public function __construct(StockCardService $stockCardService)
    {
        $this->stockCardService = $stockCardService;
    }

    /**
     * Get all stock cards
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['inventory_item_id', 'transaction_type', 'date_from', 'date_to']);
            $perPage = $request->input('per_page', 15);

            $stockCards = $this->stockCardService->getAllStockCards($filters, $perPage);

            return response()->json($stockCards);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get stock card by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $stockCard = $this->stockCardService->getStockCardById($id);

            if (!$stockCard) {
                return response()->json(['message' => 'Stock card entry not found.'], 404);
            }

            return response()->json(['data' => $stockCard]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Record stock in
     */
    public function stockIn(StockInRequest $request): JsonResponse
    {
        try {
            $stockCard = $this->stockCardService->recordStockIn($request->validated());

            return response()->json([
                'message' => 'Stock in recorded successfully.',
                'data' => $stockCard,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Record stock out
     */
    public function stockOut(StockOutRequest $request): JsonResponse
    {
        try {
            $stockCard = $this->stockCardService->recordStockOut($request->validated());

            return response()->json([
                'message' => 'Stock out recorded successfully.',
                'data' => $stockCard,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Record donation received
     */
    public function recordDonation(RecordDonationRequest $request): JsonResponse
    {
        try {
            $stockCard = $this->stockCardService->recordDonation($request->validated());

            return response()->json([
                'message' => 'Donation recorded successfully.',
                'data' => $stockCard,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get stock cards by inventory item
     */
    public function byInventoryItem(int $itemId): JsonResponse
    {
        try {
            $stockCards = $this->stockCardService->getStockCardsByItem($itemId);

            return response()->json(['data' => $stockCards]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current balance for an inventory item
     */
    public function currentBalance(int $itemId): JsonResponse
    {
        try {
            $balance = $this->stockCardService->getCurrentBalance($itemId);

            return response()->json([
                'inventory_item_id' => $itemId,
                'current_balance' => $balance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
