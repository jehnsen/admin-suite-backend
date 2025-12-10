<?php

namespace App\Repositories\Financial;

use App\Interfaces\Financial\LiquidationRepositoryInterface;
use App\Models\Liquidation;
use App\Models\LiquidationItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LiquidationRepository implements LiquidationRepositoryInterface
{
    public function getAllLiquidations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Liquidation::with(['cashAdvance.employee', 'verifiedBy', 'approvedBy', 'items']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['cash_advance_id'])) {
            $query->where('cash_advance_id', $filters['cash_advance_id']);
        }

        return $query->orderBy('liquidation_date', 'desc')->paginate($perPage);
    }

    public function getLiquidationById(int $id): ?Liquidation
    {
        return Liquidation::with([
            'cashAdvance.employee',
            'items',
            'verifiedBy',
            'approvedBy'
        ])->find($id);
    }

    public function createLiquidation(array $data): Liquidation
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $liquidation = Liquidation::create($data);

            // Create liquidation items
            foreach ($items as $index => $item) {
                $item['liquidation_id'] = $liquidation->id;
                $item['item_number'] = $index + 1;
                LiquidationItem::create($item);
            }

            // Trigger auto-calculation via model observer
            $liquidation->save();

            return $liquidation->fresh('items');
        });
    }

    public function updateLiquidation(int $id, array $data): Liquidation
    {
        return DB::transaction(function () use ($id, $data) {
            $liquidation = Liquidation::findOrFail($id);

            $items = $data['items'] ?? null;
            unset($data['items']);

            $liquidation->update($data);

            // Update items if provided
            if ($items !== null) {
                $liquidation->items()->delete();

                foreach ($items as $index => $item) {
                    $item['liquidation_id'] = $liquidation->id;
                    $item['item_number'] = $index + 1;
                    LiquidationItem::create($item);
                }

                // Trigger auto-calculation
                $liquidation->save();
            }

            return $liquidation->fresh('items');
        });
    }

    public function deleteLiquidation(int $id): bool
    {
        $liquidation = Liquidation::findOrFail($id);
        return $liquidation->delete();
    }

    public function verifyLiquidation(int $id, int $verifiedBy, ?string $remarks = null): Liquidation
    {
        $liquidation = Liquidation::findOrFail($id);
        $liquidation->update([
            'status' => 'Verified',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'verification_remarks' => $remarks,
        ]);
        return $liquidation->fresh();
    }

    public function approveLiquidation(int $id, int $approvedBy): Liquidation
    {
        $liquidation = Liquidation::findOrFail($id);
        $liquidation->update([
            'status' => 'Approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        return $liquidation->fresh();
    }

    public function rejectLiquidation(int $id, string $reason): Liquidation
    {
        $liquidation = Liquidation::findOrFail($id);
        $liquidation->update([
            'status' => 'Rejected',
            'remarks' => $reason,
        ]);
        return $liquidation->fresh();
    }

    public function getPendingLiquidations(int $perPage = 15): LengthAwarePaginator
    {
        return Liquidation::with(['cashAdvance.employee', 'items'])
            ->where('status', 'Pending')
            ->orderBy('liquidation_date', 'desc')
            ->paginate($perPage);
    }

    public function getLiquidationStatistics(): array
    {
        return [
            'total_liquidations' => Liquidation::count(),
            'pending' => Liquidation::where('status', 'Pending')->count(),
            'verified' => Liquidation::where('status', 'Verified')->count(),
            'approved' => Liquidation::where('status', 'Approved')->count(),
            'rejected' => Liquidation::where('status', 'Rejected')->count(),
            'total_liquidated' => Liquidation::where('status', 'Approved')->sum('total_expenses'),
            'total_to_refund' => Liquidation::where('status', 'Approved')->sum('amount_to_refund'),
            'total_additional_needed' => Liquidation::where('status', 'Approved')->sum('additional_cash_needed'),
        ];
    }
}
