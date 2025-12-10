<?php

namespace App\Repositories\Inventory;

use App\Interfaces\Inventory\StockCardRepositoryInterface;
use App\Models\StockCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockCardRepository implements StockCardRepositoryInterface
{
    public function getAllStockCards(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockCard::with(['inventoryItem', 'processedBy']);

        if (!empty($filters['inventory_item_id'])) {
            $query->where('inventory_item_id', $filters['inventory_item_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('transaction_date', 'desc')->paginate($perPage);
    }

    public function getStockCardById(int $id): ?StockCard
    {
        return StockCard::with(['inventoryItem', 'processedBy', 'delivery', 'issuance', 'purchaseOrder'])->find($id);
    }

    public function getStockCardsByItem(int $itemId, int $perPage = 15): LengthAwarePaginator
    {
        return StockCard::with('processedBy')
            ->where('inventory_item_id', $itemId)
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    public function createStockCard(array $data): StockCard
    {
        return StockCard::create($data);
    }

    public function getCurrentBalance(int $itemId): int
    {
        $lastCard = StockCard::where('inventory_item_id', $itemId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastCard ? $lastCard->balance : 0;
    }

    public function getStockCardHistory(int $itemId, array $filters = []): Collection
    {
        $query = StockCard::where('inventory_item_id', $itemId);

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('transaction_date')->get();
    }

    public function getStockCardStatistics(): array
    {
        return [
            'total_transactions' => StockCard::count(),
            'stock_in_count' => StockCard::where('transaction_type', 'Stock In')->count(),
            'stock_out_count' => StockCard::where('transaction_type', 'Stock Out')->count(),
            'total_stock_in_value' => StockCard::where('transaction_type', 'Stock In')->sum('total_cost'),
            'total_stock_out_value' => StockCard::where('transaction_type', 'Stock Out')->sum('total_cost'),
        ];
    }
}
