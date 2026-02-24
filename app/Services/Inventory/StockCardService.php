<?php

namespace App\Services\Inventory;

use App\Interfaces\Inventory\StockCardRepositoryInterface;
use App\Models\StockCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockCardService
{
    protected $stockCardRepository;

    public function __construct(StockCardRepositoryInterface $stockCardRepository)
    {
        $this->stockCardRepository = $stockCardRepository;
    }

    public function getAllStockCards(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->stockCardRepository->getAllStockCards($filters, $perPage);
    }

    public function getStockCardById(int $id): ?StockCard
    {
        return $this->stockCardRepository->getStockCardById($id);
    }

    public function getStockCardsByItem(int $itemId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->stockCardRepository->getStockCardsByItem($itemId, $perPage);
    }

    public function recordStockIn(array $data): StockCard
    {
        $currentBalance = $this->stockCardRepository->getCurrentBalance($data['inventory_item_id']);

        if (!isset($data['quantity_in'])) {
            $data['quantity_in'] = $data['quantity'] ?? 0;
        }

        if (empty($data['processed_by'])) {
            $data['processed_by'] = auth()->id();
        }

        $data['transaction_type'] = 'Stock In';
        $data['quantity_out'] = 0;
        $data['balance'] = $currentBalance + $data['quantity_in'];
        $data['total_cost'] = $data['quantity_in'] * $data['unit_cost'];

        return $this->stockCardRepository->createStockCard($data);
    }

    public function recordStockOut(array $data): StockCard
    {
        $currentBalance = $this->stockCardRepository->getCurrentBalance($data['inventory_item_id']);

        if ($currentBalance < $data['quantity_out']) {
            throw new \Exception('Insufficient stock. Current balance: ' . $currentBalance);
        }

        $data['transaction_type'] = 'Stock Out';
        $data['quantity_in'] = 0;
        $data['balance'] = $currentBalance - $data['quantity_out'];
        $data['total_cost'] = $data['quantity_out'] * $data['unit_cost'];

        return $this->stockCardRepository->createStockCard($data);
    }

    public function recordDonation(array $data): StockCard
    {
        $currentBalance = $this->stockCardRepository->getCurrentBalance($data['inventory_item_id']);

        $data['transaction_type'] = 'Donation';
        $data['quantity_out'] = 0;
        $data['balance'] = $currentBalance + $data['quantity_in'];
        $data['total_cost'] = $data['quantity_in'] * ($data['unit_cost'] ?? 0);

        return $this->stockCardRepository->createStockCard($data);
    }

    public function getCurrentBalance(int $itemId): int
    {
        return $this->stockCardRepository->getCurrentBalance($itemId);
    }

    public function getStockCardHistory(int $itemId, array $filters = []): Collection
    {
        return $this->stockCardRepository->getStockCardHistory($itemId, $filters);
    }

    public function getStockCardStatistics(): array
    {
        return $this->stockCardRepository->getStockCardStatistics();
    }
}
