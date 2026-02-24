<?php

namespace App\Services\Inventory;

use App\Interfaces\Inventory\PhysicalCountRepositoryInterface;
use App\Interfaces\Inventory\StockCardRepositoryInterface;
use App\Models\PhysicalCount;
use Illuminate\Pagination\LengthAwarePaginator;

class PhysicalCountService
{
    protected $countRepository;
    protected $stockCardRepository;

    public function __construct(
        PhysicalCountRepositoryInterface $countRepository,
        StockCardRepositoryInterface $stockCardRepository
    ) {
        $this->countRepository = $countRepository;
        $this->stockCardRepository = $stockCardRepository;
    }

    public function getAllCounts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->countRepository->getAllCounts($filters, $perPage);
    }

    public function getCountById(int $id): ?PhysicalCount
    {
        return $this->countRepository->getCountById($id);
    }

    public function createCount(array $data): PhysicalCount
    {
        // Generate count number if not provided
        if (empty($data['count_number'])) {
            $data['count_number'] = $this->generateCountNumber();
        }

        // Get system quantity from stock card
        $data['system_quantity'] = $this->stockCardRepository->getCurrentBalance($data['inventory_item_id']);

        return $this->countRepository->createCount($data);
    }

    public function updateCount(int $id, array $data): PhysicalCount
    {
        return $this->countRepository->updateCount($id, $data);
    }

    public function verifyCount(int $id, int $verifiedBy): PhysicalCount
    {
        return $this->countRepository->verifyCount($id, $verifiedBy);
    }

    public function getCountsWithVariance(int $perPage = 15): LengthAwarePaginator
    {
        return $this->countRepository->getCountsWithVariance($perPage);
    }

    public function getCountStatistics(): array
    {
        return $this->countRepository->getCountStatistics();
    }

    private function generateCountNumber(): string
    {
        $year = date('Y');
        $lastCount = PhysicalCount::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastCount ? ((int) substr($lastCount->count_number, -4)) + 1 : 1;

        return 'PC-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
