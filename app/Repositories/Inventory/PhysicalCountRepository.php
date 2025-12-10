<?php

namespace App\Repositories\Inventory;

use App\Interfaces\Inventory\PhysicalCountRepositoryInterface;
use App\Models\PhysicalCount;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PhysicalCountRepository implements PhysicalCountRepositoryInterface
{
    public function getAllCounts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PhysicalCount::with(['inventoryItem', 'countedBy', 'verifiedBy']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['variance_type'])) {
            $query->where('variance_type', $filters['variance_type']);
        }

        if (!empty($filters['count_date_from'])) {
            $query->whereDate('count_date', '>=', $filters['count_date_from']);
        }

        if (!empty($filters['count_date_to'])) {
            $query->whereDate('count_date', '<=', $filters['count_date_to']);
        }

        return $query->orderBy('count_date', 'desc')->paginate($perPage);
    }

    public function getCountById(int $id): ?PhysicalCount
    {
        return PhysicalCount::with(['inventoryItem', 'countedBy', 'verifiedBy'])->find($id);
    }

    public function createCount(array $data): PhysicalCount
    {
        return PhysicalCount::create($data);
    }

    public function updateCount(int $id, array $data): PhysicalCount
    {
        $count = PhysicalCount::findOrFail($id);
        $count->update($data);
        return $count->fresh();
    }

    public function verifyCount(int $id, int $verifiedBy): PhysicalCount
    {
        $count = PhysicalCount::findOrFail($id);
        $count->update([
            'status' => 'Verified',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);
        return $count->fresh();
    }

    public function getCountsWithVariance(int $perPage = 15): LengthAwarePaginator
    {
        return PhysicalCount::with(['inventoryItem', 'countedBy'])
            ->where('variance', '!=', 0)
            ->orderBy('count_date', 'desc')
            ->paginate($perPage);
    }

    public function getCountStatistics(): array
    {
        return [
            'total_counts' => PhysicalCount::count(),
            'counts_with_variance' => PhysicalCount::where('variance', '!=', 0)->count(),
            'shortages' => PhysicalCount::where('variance_type', 'Shortage')->count(),
            'overages' => PhysicalCount::where('variance_type', 'Overage')->count(),
            'matches' => PhysicalCount::where('variance_type', 'Match')->count(),
            'total_shortage_qty' => PhysicalCount::where('variance', '<', 0)->sum(DB::raw('ABS(variance)')),
            'total_overage_qty' => PhysicalCount::where('variance', '>', 0)->sum('variance'),
        ];
    }
}
