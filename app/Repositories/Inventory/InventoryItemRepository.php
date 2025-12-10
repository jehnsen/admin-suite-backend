<?php

namespace App\Repositories\Inventory;

use App\Interfaces\Inventory\InventoryItemRepositoryInterface;
use App\Models\InventoryItem;
use App\Models\StockCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryItemRepository implements InventoryItemRepositoryInterface
{
    public function getAllInventoryItems(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = InventoryItem::query();

        // Apply filters
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        if (!empty($filters['fund_source'])) {
            $query->where('fund_source', $filters['fund_source']);
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }

        return $query->orderBy('item_name')->paginate($perPage);
    }

    public function getInventoryItemById(int $id): ?InventoryItem
    {
        return InventoryItem::find($id);
    }

    public function createInventoryItem(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    public function updateInventoryItem(int $id, array $data): InventoryItem
    {
        $item = InventoryItem::findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }

    public function deleteInventoryItem(int $id): bool
    {
        $item = InventoryItem::findOrFail($id);
        return $item->delete();
    }

    public function searchInventoryItems(string $searchTerm, int $perPage = 15): LengthAwarePaginator
    {
        return InventoryItem::where(function ($query) use ($searchTerm) {
            $query->where('item_code', 'like', '%' . $searchTerm . '%')
                ->orWhere('item_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%')
                ->orWhere('property_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('serial_number', 'like', '%' . $searchTerm . '%');
        })
            ->orderBy('item_name')
            ->paginate($perPage);
    }

    public function getInventoryItemsWithCurrentBalance(): Collection
    {
        return InventoryItem::select('inventory_items.*')
            ->leftJoin('stock_cards as sc', function ($join) {
                $join->on('inventory_items.id', '=', 'sc.inventory_item_id')
                    ->whereRaw('sc.id = (SELECT id FROM stock_cards WHERE inventory_item_id = inventory_items.id ORDER BY created_at DESC LIMIT 1)');
            })
            ->addSelect(DB::raw('COALESCE(sc.balance, 0) as current_stock_balance'))
            ->orderBy('inventory_items.item_name')
            ->get();
    }

    public function getLowStockItems(int $threshold = 10): Collection
    {
        return DB::table('inventory_items')
            ->select('inventory_items.*')
            ->leftJoin('stock_cards as sc', function ($join) {
                $join->on('inventory_items.id', '=', 'sc.inventory_item_id')
                    ->whereRaw('sc.id = (SELECT id FROM stock_cards WHERE inventory_item_id = inventory_items.id ORDER BY created_at DESC LIMIT 1)');
            })
            ->addSelect(DB::raw('COALESCE(sc.balance, 0) as current_stock_balance'))
            ->whereRaw('COALESCE(sc.balance, 0) <= ?', [$threshold])
            ->orderBy('current_stock_balance')
            ->get();
    }

    public function getInventoryStatistics(): array
    {
        // Get total items
        $totalItems = InventoryItem::count();

        // Get items by status
        $inStock = InventoryItem::where('status', 'In Stock')->count();
        $issued = InventoryItem::where('status', 'Issued')->count();

        // Get items by condition
        $serviceable = InventoryItem::where('condition', 'Serviceable')->count();
        $unserviceable = InventoryItem::where('condition', 'Unserviceable')->count();

        // Get total value
        $totalValue = InventoryItem::sum('total_cost');
        $totalBookValue = InventoryItem::sum('book_value');

        // Get category breakdown
        $categoryBreakdown = InventoryItem::select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category')
            ->toArray();

        return [
            'total_items' => $totalItems,
            'in_stock' => $inStock,
            'issued' => $issued,
            'serviceable' => $serviceable,
            'unserviceable' => $unserviceable,
            'total_value' => $totalValue,
            'total_book_value' => $totalBookValue,
            'category_breakdown' => $categoryBreakdown,
        ];
    }
}
