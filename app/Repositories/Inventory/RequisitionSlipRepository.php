<?php

namespace App\Repositories\Inventory;

use App\Interfaces\Inventory\RequisitionSlipRepositoryInterface;
use App\Models\RequisitionSlip;
use App\Models\RequisitionSlipItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RequisitionSlipRepository implements RequisitionSlipRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RequisitionSlip::with([
            'requestedByEmployee',
            'approvedByEmployee',
            'releasedByEmployee',
            'items.inventoryItem',
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('requested_by_employee_id', $filters['employee_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('requested_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('requested_date', '<=', $filters['to_date']);
        }

        return $query->orderByDesc('requested_date')->paginate($perPage);
    }

    public function getById(int $id): ?RequisitionSlip
    {
        return RequisitionSlip::with([
            'requestedByEmployee',
            'approvedByEmployee',
            'releasedByEmployee',
            'items.inventoryItem',
        ])->find($id);
    }

    public function create(array $data, array $items): RequisitionSlip
    {
        return DB::transaction(function () use ($data, $items) {
            $slip = RequisitionSlip::create($data);

            foreach ($items as $item) {
                RequisitionSlipItem::create(array_merge($item, [
                    'requisition_slip_id' => $slip->id,
                ]));
            }

            return $slip->fresh(['requestedByEmployee', 'items.inventoryItem']);
        });
    }

    public function update(int $id, array $data, ?array $items = null): RequisitionSlip
    {
        return DB::transaction(function () use ($id, $data, $items) {
            $slip = RequisitionSlip::findOrFail($id);
            $slip->update($data);

            if ($items !== null) {
                $slip->items()->delete();
                foreach ($items as $item) {
                    RequisitionSlipItem::create(array_merge($item, [
                        'requisition_slip_id' => $slip->id,
                    ]));
                }
            }

            return $slip->fresh(['requestedByEmployee', 'items.inventoryItem']);
        });
    }

    public function delete(int $id): bool
    {
        $slip = RequisitionSlip::findOrFail($id);
        return $slip->delete();
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return RequisitionSlip::with(['requestedByEmployee', 'items'])
            ->where('ris_number', 'like', "%{$term}%")
            ->orWhere('purpose', 'like', "%{$term}%")
            ->orWhereHas('requestedByEmployee', fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%"))
            ->orderByDesc('requested_date')
            ->paginate($perPage);
    }

    public function getPending(int $perPage = 15): LengthAwarePaginator
    {
        return RequisitionSlip::with(['requestedByEmployee', 'items'])
            ->pending()
            ->orderBy('requested_date')
            ->paginate($perPage);
    }

    public function approve(int $id, int $approvedByEmployeeId, array $approvedQuantities): RequisitionSlip
    {
        return DB::transaction(function () use ($id, $approvedByEmployeeId, $approvedQuantities) {
            $slip = RequisitionSlip::with('items')->findOrFail($id);

            foreach ($approvedQuantities as $itemId => $qty) {
                $slip->items()->where('inventory_item_id', $itemId)->update([
                    'quantity_approved' => $qty,
                ]);
            }

            $slip->update([
                'status'                    => 'Approved',
                'approved_by_employee_id'   => $approvedByEmployeeId,
                'approved_date'             => now()->toDateString(),
            ]);

            return $slip->fresh(['requestedByEmployee', 'approvedByEmployee', 'items.inventoryItem']);
        });
    }

    public function release(int $id, int $releasedByEmployeeId, array $issuedQuantities): RequisitionSlip
    {
        return DB::transaction(function () use ($id, $releasedByEmployeeId, $issuedQuantities) {
            $slip = RequisitionSlip::with('items')->findOrFail($id);

            foreach ($issuedQuantities as $itemId => $qty) {
                $slip->items()->where('inventory_item_id', $itemId)->update([
                    'quantity_issued' => $qty,
                ]);
            }

            $slip->update([
                'status'                    => 'Released',
                'released_by_employee_id'   => $releasedByEmployeeId,
                'released_date'             => now()->toDateString(),
            ]);

            return $slip->fresh(['requestedByEmployee', 'releasedByEmployee', 'items.inventoryItem']);
        });
    }

    public function cancel(int $id, string $remarks = ''): RequisitionSlip
    {
        $slip = RequisitionSlip::findOrFail($id);
        $slip->update([
            'status'  => 'Cancelled',
            'remarks' => $remarks ?: $slip->remarks,
        ]);
        return $slip->fresh();
    }

    public function getStatistics(): array
    {
        $total    = RequisitionSlip::count();
        $draft    = RequisitionSlip::where('status', 'Draft')->count();
        $pending  = RequisitionSlip::where('status', 'Pending')->count();
        $approved = RequisitionSlip::where('status', 'Approved')->count();
        $released = RequisitionSlip::where('status', 'Released')->count();
        $cancelled= RequisitionSlip::where('status', 'Cancelled')->count();

        return [
            'total'     => $total,
            'draft'     => $draft,
            'pending'   => $pending,
            'approved'  => $approved,
            'released'  => $released,
            'cancelled' => $cancelled,
        ];
    }

    public function generateRisNumber(): string
    {
        $year = now()->year;

        $latest = RequisitionSlip::where('ris_number', 'like', "RIS-{$year}-%")
            ->orderByDesc('ris_number')
            ->value('ris_number');

        $sequence = $latest
            ? (int) substr($latest, strrpos($latest, '-') + 1) + 1
            : 1;

        return sprintf('RIS-%d-%04d', $year, $sequence);
    }
}
