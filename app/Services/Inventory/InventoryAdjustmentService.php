<?php

namespace App\Services\Inventory;

use App\Interfaces\Inventory\InventoryAdjustmentRepositoryInterface;
use App\Interfaces\Inventory\StockCardRepositoryInterface;
use App\Models\InventoryAdjustment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentService
{
    protected $adjustmentRepository;
    protected $stockCardRepository;

    public function __construct(
        InventoryAdjustmentRepositoryInterface $adjustmentRepository,
        StockCardRepositoryInterface $stockCardRepository
    ) {
        $this->adjustmentRepository = $adjustmentRepository;
        $this->stockCardRepository = $stockCardRepository;
    }

    public function getAllAdjustments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->adjustmentRepository->getAllAdjustments($filters, $perPage);
    }

    public function getAdjustmentById(int $id): ?InventoryAdjustment
    {
        return $this->adjustmentRepository->getAdjustmentById($id);
    }

    public function createAdjustment(array $data): InventoryAdjustment
    {
        // Generate adjustment number if not provided
        if (empty($data['adjustment_number'])) {
            $data['adjustment_number'] = $this->generateAdjustmentNumber();
        }

        if (!isset($data['quantity_adjusted']) && isset($data['quantity'])) {
            $sign = ($data['adjustment_type'] === 'Decrease') ? -1 : 1;
            $data['quantity_adjusted'] = $sign * $data['quantity'];
        }

        if (empty($data['prepared_by'])) {
            $data['prepared_by'] = auth()->id();
        }

        // Get current balance
        $currentBalance = $this->stockCardRepository->getCurrentBalance($data['inventory_item_id']);
        $data['quantity_before'] = $currentBalance;
        $data['quantity_after'] = $currentBalance + $data['quantity_adjusted'];

        return $this->adjustmentRepository->createAdjustment($data);
    }

    public function updateAdjustment(int $id, array $data): InventoryAdjustment
    {
        $adjustment = $this->adjustmentRepository->getAdjustmentById($id);

        if ($adjustment->status !== 'Pending') {
            throw new \Exception('Only pending adjustments can be updated.');
        }

        return $this->adjustmentRepository->updateAdjustment($id, $data);
    }

    public function deleteAdjustment(int $id): bool
    {
        $adjustment = $this->adjustmentRepository->getAdjustmentById($id);

        if ($adjustment->status !== 'Pending') {
            throw new \Exception('Only pending adjustments can be deleted.');
        }

        return $this->adjustmentRepository->deleteAdjustment($id);
    }

    public function approveAdjustment(int $id, int $approvedBy): InventoryAdjustment
    {
        return DB::transaction(function () use ($id, $approvedBy) {
            $adjustment = $this->adjustmentRepository->getAdjustmentById($id);

            if (!$adjustment->canBeApproved()) {
                throw new \Exception('Adjustment cannot be approved in current status.');
            }

            // Approve the adjustment
            $adjustment = $this->adjustmentRepository->approveAdjustment($id, $approvedBy);

            // Create stock card entry
            $stockCardData = [
                'inventory_item_id' => $adjustment->inventory_item_id,
                'transaction_date' => $adjustment->adjustment_date,
                'reference_number' => $adjustment->adjustment_number,
                'transaction_type' => 'Adjustment',
                'source_destination' => $adjustment->adjustment_type,
                'processed_by' => $approvedBy,
                'remarks' => $adjustment->reason,
                'unit_cost' => 0, // Adjustments don't change cost
            ];

            if ($adjustment->quantity_adjusted > 0) {
                $stockCardData['quantity_in'] = $adjustment->quantity_adjusted;
                $stockCardData['quantity_out'] = 0;
            } else {
                $stockCardData['quantity_in'] = 0;
                $stockCardData['quantity_out'] = abs($adjustment->quantity_adjusted);
            }

            $stockCardData['balance'] = $adjustment->quantity_after;
            $stockCardData['total_cost'] = 0;

            $this->stockCardRepository->createStockCard($stockCardData);

            return $adjustment;
        });
    }

    public function rejectAdjustment(int $id, string $reason): InventoryAdjustment
    {
        return $this->adjustmentRepository->rejectAdjustment($id, $reason);
    }

    public function getPendingAdjustments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->adjustmentRepository->getPendingAdjustments($perPage);
    }

    public function getAdjustmentStatistics(): array
    {
        return $this->adjustmentRepository->getAdjustmentStatistics();
    }

    private function generateAdjustmentNumber(): string
    {
        $year = date('Y');
        $lastAdjustment = InventoryAdjustment::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastAdjustment ? ((int) substr($lastAdjustment->adjustment_number, -4)) + 1 : 1;

        return 'ADJ-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
