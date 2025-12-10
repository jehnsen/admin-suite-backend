<?php

namespace App\Interfaces\Inventory;

use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InventoryItemRepositoryInterface
{
    public function getAllInventoryItems(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getInventoryItemById(int $id): ?InventoryItem;
    public function createInventoryItem(array $data): InventoryItem;
    public function updateInventoryItem(int $id, array $data): InventoryItem;
    public function deleteInventoryItem(int $id): bool;
    public function searchInventoryItems(string $searchTerm, int $perPage = 15): LengthAwarePaginator;
    public function getInventoryItemsWithCurrentBalance(): Collection;
    public function getLowStockItems(int $threshold = 10): Collection;
    public function getInventoryStatistics(): array;
}
