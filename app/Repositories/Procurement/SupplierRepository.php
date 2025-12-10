<?php

namespace App\Repositories\Procurement;

use App\Interfaces\Procurement\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function getAllSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::query();

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['business_type'])) {
            $query->where('business_type', $filters['business_type']);
        }

        if (!empty($filters['classification'])) {
            $query->where('supplier_classification', $filters['classification']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('business_name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('supplier_code', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('owner_name', 'LIKE', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('business_name')->paginate($perPage);
    }

    public function getSupplierById(int $id): ?Supplier
    {
        return Supplier::with(['quotations', 'purchaseOrders'])->find($id);
    }

    public function getSupplierByCode(string $code): ?Supplier
    {
        return Supplier::where('supplier_code', $code)->first();
    }

    public function createSupplier(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function updateSupplier(int $id, array $data): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function deleteSupplier(int $id): bool
    {
        $supplier = Supplier::findOrFail($id);
        return $supplier->delete();
    }

    public function getActiveSuppliers(int $perPage = 15): LengthAwarePaginator
    {
        return Supplier::where('status', 'Active')
            ->orderBy('business_name')
            ->paginate($perPage);
    }

    public function searchSuppliers(string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        return Supplier::where('business_name', 'LIKE', "%{$keyword}%")
            ->orWhere('supplier_code', 'LIKE', "%{$keyword}%")
            ->orWhere('trade_name', 'LIKE', "%{$keyword}%")
            ->orderBy('business_name')
            ->paginate($perPage);
    }

    public function updatePerformanceMetrics(int $id, float $rating, float $amount): Supplier
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->total_transactions += 1;
        $supplier->total_amount_transacted += $amount;

        // Calculate new average rating
        $currentTotal = $supplier->rating * ($supplier->total_transactions - 1);
        $supplier->rating = ($currentTotal + $rating) / $supplier->total_transactions;

        $supplier->save();

        return $supplier;
    }

    public function getSupplierStatistics(): array
    {
        return [
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::where('status', 'Active')->count(),
            'inactive_suppliers' => Supplier::where('status', 'Inactive')->count(),
            'blacklisted_suppliers' => Supplier::where('status', 'Blacklisted')->count(),
            'by_business_type' => Supplier::select('business_type', DB::raw('count(*) as count'))
                ->groupBy('business_type')
                ->get()
                ->pluck('count', 'business_type'),
            'average_rating' => round(Supplier::where('total_transactions', '>', 0)->avg('rating'), 2),
        ];
    }
}
