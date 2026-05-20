<?php

namespace App\Repositories\Inventory;

use App\Interfaces\Inventory\IssuanceRepositoryInterface;
use App\Models\Issuance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class IssuanceRepository implements IssuanceRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Issuance::with([
            'inventoryItem',
            'issuedToEmployee',
            'issuedByEmployee',
            'approvedByEmployee',
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('issued_to_employee_id', $filters['employee_id']);
        }

        if (!empty($filters['inventory_item_id'])) {
            $query->where('inventory_item_id', $filters['inventory_item_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('issued_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('issued_date', '<=', $filters['to_date']);
        }

        return $query->orderByDesc('issued_date')->paginate($perPage);
    }

    public function getById(int $id): ?Issuance
    {
        return Issuance::with([
            'inventoryItem',
            'issuedToEmployee',
            'issuedByEmployee',
            'approvedByEmployee',
        ])->find($id);
    }

    public function create(array $data): Issuance
    {
        return Issuance::create($data);
    }

    public function update(int $id, array $data): Issuance
    {
        $issuance = Issuance::findOrFail($id);
        $issuance->update($data);
        return $issuance->fresh(['inventoryItem', 'issuedToEmployee', 'issuedByEmployee', 'approvedByEmployee']);
    }

    public function delete(int $id): bool
    {
        $issuance = Issuance::findOrFail($id);
        return $issuance->delete();
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return Issuance::with(['inventoryItem', 'issuedToEmployee'])
            ->where('issuance_number', 'like', "%{$term}%")
            ->orWhereHas('inventoryItem', fn ($q) => $q
                ->where('item_name', 'like', "%{$term}%")
                ->orWhere('property_number', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%"))
            ->orWhereHas('issuedToEmployee', fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%"))
            ->orderByDesc('issued_date')
            ->paginate($perPage);
    }

    public function getOverdue(int $perPage = 15): LengthAwarePaginator
    {
        return Issuance::with(['inventoryItem', 'issuedToEmployee'])
            ->overdue()
            ->orderBy('expected_return_date')
            ->paginate($perPage);
    }

    public function getByEmployee(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return Issuance::with(['inventoryItem'])
            ->byEmployee($employeeId)
            ->where('status', 'Active')
            ->orderByDesc('issued_date')
            ->paginate($perPage);
    }

    public function acknowledge(int $id, array $data): Issuance
    {
        $issuance = Issuance::findOrFail($id);
        $issuance->update([
            'acknowledged_at'                => now(),
            'acknowledgement_signature_path' => $data['signature_path'] ?? null,
        ]);
        return $issuance->fresh();
    }

    public function recordReturn(int $id, array $data): Issuance
    {
        $issuance = Issuance::findOrFail($id);
        $issuance->update([
            'status'             => $data['condition_on_return'] === 'Damaged' ? 'Damaged' : 'Returned',
            'actual_return_date' => $data['actual_return_date'] ?? now()->toDateString(),
            'condition_on_return'=> $data['condition_on_return'],
            'return_remarks'     => $data['return_remarks'] ?? null,
        ]);
        return $issuance->fresh(['inventoryItem', 'issuedToEmployee']);
    }

    public function transfer(int $id, int $newEmployeeId, string $remarks = ''): Issuance
    {
        $issuance = Issuance::findOrFail($id);
        $issuance->update([
            'status'                  => 'Transferred',
            'actual_return_date'      => now()->toDateString(),
            'return_remarks'          => $remarks,
        ]);

        // Create a new issuance record for the new employee
        $newIssuance = Issuance::create([
            'document_type'           => $issuance->document_type,
            'inventory_item_id'       => $issuance->inventory_item_id,
            'issued_to_employee_id'   => $newEmployeeId,
            'issuance_number'         => $this->generateIssuanceNumber($issuance->document_type),
            'issued_date'             => now()->toDateString(),
            'expected_return_date'    => $issuance->expected_return_date,
            'purpose'                 => $issuance->purpose,
            'purpose_details'         => "Transferred from issuance #{$issuance->issuance_number}. {$remarks}",
            'custodianship_type'      => $issuance->custodianship_type,
            'status'                  => 'Active',
            'issued_by'               => $issuance->issued_by,
            'approved_by'             => $issuance->approved_by,
            'remarks'                 => $remarks,
        ]);

        return $newIssuance->fresh(['inventoryItem', 'issuedToEmployee']);
    }

    public function getStatistics(): array
    {
        $total     = Issuance::count();
        $active    = Issuance::where('status', 'Active')->count();
        $overdue   = Issuance::overdue()->count();
        $returned  = Issuance::where('status', 'Returned')->count();
        $lost      = Issuance::where('status', 'Lost')->count();
        $damaged   = Issuance::where('status', 'Damaged')->count();

        $byDocType = Issuance::select('document_type', DB::raw('COUNT(*) as count'))
            ->groupBy('document_type')
            ->get()
            ->pluck('count', 'document_type')
            ->toArray();

        return [
            'total'              => $total,
            'active'             => $active,
            'overdue'            => $overdue,
            'returned'           => $returned,
            'lost'               => $lost,
            'damaged'            => $damaged,
            'by_document_type'   => $byDocType,
        ];
    }

    public function createBatch(array $shared, array $items): \Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($shared, $items) {
            $created = collect();

            foreach ($items as $item) {
                $data = array_merge($shared, [
                    'document_type'     => $item['document_type'],
                    'inventory_item_id' => $item['inventory_item_id'],
                    'issuance_number'   => $this->generateIssuanceNumber($item['document_type']),
                ]);

                if (isset($item['purpose'])) {
                    $data['purpose'] = $item['purpose'];
                }
                if (isset($item['remarks'])) {
                    $data['remarks'] = $item['remarks'];
                }

                $created->push(Issuance::create($data));
            }

            return $created->load(['inventoryItem', 'issuedToEmployee', 'issuedByEmployee', 'approvedByEmployee']);
        });
    }

    public function generateIssuanceNumber(string $type = 'General'): string
    {
        $prefix = match ($type) {
            'PAR'   => 'PAR',
            'ICS'   => 'ICS',
            default => 'IS',
        };

        $year = now()->year;

        $latest = Issuance::where('issuance_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('issuance_number')
            ->value('issuance_number');

        $sequence = $latest
            ? (int) substr($latest, strrpos($latest, '-') + 1) + 1
            : 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }
}
