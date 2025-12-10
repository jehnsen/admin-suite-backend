<?php

namespace App\Interfaces\Inventory;

use App\Models\StockCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockCardRepositoryInterface
{
    public function getAllStockCards(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getStockCardById(int $id): ?StockCard;
    public function getStockCardsByItem(int $itemId, int $perPage = 15): LengthAwarePaginator;
    public function createStockCard(array $data): StockCard;
    public function getCurrentBalance(int $itemId): int;
    public function getStockCardHistory(int $itemId, array $filters = []): Collection;
    public function getStockCardStatistics(): array;
}
