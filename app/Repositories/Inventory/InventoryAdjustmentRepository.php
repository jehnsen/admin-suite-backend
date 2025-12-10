<?php

namespace App\Repositories\Inventory;

use App\Interfaces\Inventory\InventoryAdjustmentRepositoryInterface;
use App\Models\InventoryAdjustment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentRepository implements InventoryAdjustmentRepositoryInterface
{
    public function getAllAdjustments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = InventoryAdjustment::with(['inventoryItem', 'preparedBy', 'approvedBy']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['adjustment_type'])) {
            $query->where('adjustment_type', $filters['adjustment_type']);
        }

        if (!empty($filters['inventory_item_id'])) {
            $query->where('inventory_item_id', $filters['inventory_item_id']);
        }

        return $query->orderBy('adjustment_date', 'desc')->paginate($perPage);
    }

    public function getAdjustmentById(int $id): ?InventoryAdjustment
    {
        return InventoryAdjustment::with(['inventoryItem', 'preparedBy', 'approvedBy'])->find($id);
    }

    public function createAdjustment(array $data): InventoryAdjustment
    {
        return InventoryAdjustment::create($data);
    }

    public function updateAdjustment(int $id, array $data): InventoryAdjustment
    {
        $adjustment = InventoryAdjustment::findOrFail($id);
        $adjustment->update($data);
        return $adjustment->fresh();
    }

    public function deleteAdjustment(int $id): bool
    {
        $adjustment = InventoryAdjustment::findOrFail($id);
        return $adjustment->delete();
    }

    public function approveAdjustment(int $id, int $approvedBy): InventoryAdjustment
    {
        $adjustment = InventoryAdjustment::findOrFail($id);
        $adjustment->update([
            'status' => 'Approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        return $adjustment->fresh();
    }

    public function rejectAdjustment(int $id, string $reason): InventoryAdjustment
    {
        $adjustment = InventoryAdjustment::findOrFail($id);
        $adjustment->update([
            'status' => 'Rejected',
            'remarks' => $reason,
        ]);
        return $adjustment->fresh();
    }

    public function getPendingAdjustments(int $perPage = 15): LengthAwarePaginator
    {
        return InventoryAdjustment::with(['inventoryItem', 'preparedBy'])
            ->where('status', 'Pending')
            ->orderBy('adjustment_date', 'desc')
            ->paginate($perPage);
    }

    public function getAdjustmentStatistics(): array
    {
        return [
            'total_adjustments' => InventoryAdjustment::count(),
            'pending' => InventoryAdjustment::where('status', 'Pending')->count(),
            'approved' => InventoryAdjustment::where('status', 'Approved')->count(),
            'rejected' => InventoryAdjustment::where('status', 'Rejected')->count(),
            'by_type' => InventoryAdjustment::select('adjustment_type', DB::raw('count(*) as count'))
                ->groupBy('adjustment_type')
                ->get()
                ->pluck('count', 'adjustment_type'),
        ];
    }
}
