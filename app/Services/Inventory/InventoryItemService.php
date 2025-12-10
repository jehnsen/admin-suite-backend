<?php

namespace App\Services\Inventory;

use App\Interfaces\Inventory\InventoryItemRepositoryInterface;
use App\Interfaces\Inventory\StockCardRepositoryInterface;
use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InventoryItemService
{
    protected $inventoryItemRepository;
    protected $stockCardRepository;

    public function __construct(
        InventoryItemRepositoryInterface $inventoryItemRepository,
        StockCardRepositoryInterface $stockCardRepository
    ) {
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->stockCardRepository = $stockCardRepository;
    }

    public function getAllInventoryItems(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->inventoryItemRepository->getAllInventoryItems($filters, $perPage);
    }

    public function getInventoryItemById(int $id): ?InventoryItem
    {
        return $this->inventoryItemRepository->getInventoryItemById($id);
    }

    public function getInventoryItemWithBalance(int $id): ?array
    {
        $item = $this->inventoryItemRepository->getInventoryItemById($id);

        if (!$item) {
            return null;
        }

        $currentBalance = $this->stockCardRepository->getCurrentBalance($id);

        return [
            'item' => $item,
            'current_stock_balance' => $currentBalance,
        ];
    }

    public function createInventoryItem(array $data): InventoryItem
    {
        return $this->inventoryItemRepository->createInventoryItem($data);
    }

    public function updateInventoryItem(int $id, array $data): InventoryItem
    {
        return $this->inventoryItemRepository->updateInventoryItem($id, $data);
    }

    public function deleteInventoryItem(int $id): bool
    {
        return $this->inventoryItemRepository->deleteInventoryItem($id);
    }

    public function searchInventoryItems(string $searchTerm, int $perPage = 15): LengthAwarePaginator
    {
        return $this->inventoryItemRepository->searchInventoryItems($searchTerm, $perPage);
    }

    public function getInventoryItemsWithCurrentBalance(): Collection
    {
        return $this->inventoryItemRepository->getInventoryItemsWithCurrentBalance();
    }

    public function getLowStockItems(int $threshold = 10): Collection
    {
        return $this->inventoryItemRepository->getLowStockItems($threshold);
    }

    public function getInventoryStatistics(): array
    {
        return $this->inventoryItemRepository->getInventoryStatistics();
    }
}
