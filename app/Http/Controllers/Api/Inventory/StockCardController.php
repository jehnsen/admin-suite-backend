<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\StockCardResource;
use App\Services\Inventory\StockCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only(['inventory_item_id', 'transaction_type', 'date_from', 'date_to']);
            $perPage = $this->getPerPage($request);

            $stockCards = $this->stockCardService->getAllStockCards($filters, $perPage);

            return StockCardResource::collection($stockCards);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\StockCard::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $stockCard = $this->stockCardService->getStockCardById($id);

            if (!$stockCard) {
                return response()->json(['message' => 'Stock card entry not found.'], 404);
            }

            return response()->json(['data' => new StockCardResource($stockCard)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function stockIn(StockInRequest $request): JsonResponse
    {
        try {
            $stockCard = $this->stockCardService->recordStockIn($request->validated());

            return response()->json([
                'message' => 'Stock in recorded successfully.',
                'data'    => new StockCardResource($stockCard),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function stockOut(StockOutRequest $request): JsonResponse
    {
        try {
            $stockCard = $this->stockCardService->recordStockOut($request->validated());

            return response()->json([
                'message' => 'Stock out recorded successfully.',
                'data'    => new StockCardResource($stockCard),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function recordDonation(RecordDonationRequest $request): JsonResponse
    {
        try {
            $stockCard = $this->stockCardService->recordDonation($request->validated());

            return response()->json([
                'message' => 'Donation recorded successfully.',
                'data'    => new StockCardResource($stockCard),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function byInventoryItem(int $itemId): JsonResponse
    {
        try {
            $stockCards = $this->stockCardService->getStockCardsByItem($itemId);

            return response()->json(['data' => StockCardResource::collection($stockCards)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function currentBalance(int $itemId): JsonResponse
    {
        try {
            $balance = $this->stockCardService->getCurrentBalance($itemId);

            return response()->json([
                'inventory_item_id' => $itemId,
                'current_balance'   => $balance,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
