<?php

namespace App\Repositories\Procurement;

use App\Interfaces\Procurement\PurchaseRequestRepositoryInterface;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseRequestRepository implements PurchaseRequestRepositoryInterface
{
    public function getAllPurchaseRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseRequest::with(['requestedBy', 'items']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['fund_source'])) {
            $query->where('fund_source', $filters['fund_source']);
        }

        if (!empty($filters['procurement_mode'])) {
            $query->where('procurement_mode', $filters['procurement_mode']);
        }

        if (!empty($filters['requested_by'])) {
            $query->where('requested_by', $filters['requested_by']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('pr_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('pr_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('pr_number', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('purpose', 'LIKE', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('pr_date', 'desc')->paginate($perPage);
    }

    public function getPurchaseRequestById(int $id): ?PurchaseRequest
    {
        return PurchaseRequest::with([
            'requestedBy',
            'recommendedBy',
            'approvedBy',
            'disapprovedBy',
            'items',
            'quotations.supplier'
        ])->find($id);
    }

    public function getPurchaseRequestByNumber(string $prNumber): ?PurchaseRequest
    {
        return PurchaseRequest::with('items')->where('pr_number', $prNumber)->first();
    }

    public function createPurchaseRequest(array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $pr = PurchaseRequest::create($data);

            // Create PR items
            foreach ($items as $index => $item) {
                $item['purchase_request_id'] = $pr->id;
                $item['item_number'] = $index + 1;
                PurchaseRequestItem::create($item);
            }

            // Calculate total amount
            $this->calculateTotalAmount($pr->id);

            return $pr->fresh('items');
        });
    }

    public function updatePurchaseRequest(int $id, array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($id, $data) {
            $pr = PurchaseRequest::findOrFail($id);

            $items = $data['items'] ?? null;
            unset($data['items']);

            $pr->update($data);

            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $pr->items()->delete();

                // Create new items
                foreach ($items as $index => $item) {
                    $item['purchase_request_id'] = $pr->id;
                    $item['item_number'] = $index + 1;
                    PurchaseRequestItem::create($item);
                }

                // Recalculate total amount
                $this->calculateTotalAmount($pr->id);
            }

            return $pr->fresh('items');
        });
    }

    public function deletePurchaseRequest(int $id): bool
    {
        $pr = PurchaseRequest::findOrFail($id);
        return $pr->delete();
    }

    public function getPendingPurchaseRequests(int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseRequest::with(['requestedBy', 'items'])
            ->where('status', 'Pending')
            ->orderBy('pr_date', 'desc')
            ->paginate($perPage);
    }

    public function getApprovedPurchaseRequests(int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseRequest::with(['requestedBy', 'items'])
            ->whereIn('status', ['Approved', 'For Quotation'])
            ->orderBy('pr_date', 'desc')
            ->paginate($perPage);
    }

    public function updateStatus(int $id, string $status, array $metadata = []): PurchaseRequest
    {
        $pr = PurchaseRequest::findOrFail($id);

        $updateData = ['status' => $status];
        $updateData = array_merge($updateData, $metadata);

        $pr->update($updateData);

        return $pr->fresh();
    }

    public function getByRequestor(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseRequest::with('items')
            ->where('requested_by', $userId)
            ->orderBy('pr_date', 'desc')
            ->paginate($perPage);
    }

    public function getPurchaseRequestStatistics(): array
    {
        return [
            'total_prs' => PurchaseRequest::count(),
            'pending' => PurchaseRequest::where('status', 'Pending')->count(),
            'approved' => PurchaseRequest::where('status', 'Approved')->count(),
            'completed' => PurchaseRequest::where('status', 'Completed')->count(),
            'cancelled' => PurchaseRequest::where('status', 'Cancelled')->count(),
            'total_estimated_budget' => PurchaseRequest::sum('estimated_budget'),
            'by_fund_source' => PurchaseRequest::select('fund_source', DB::raw('count(*) as count'))
                ->groupBy('fund_source')
                ->get()
                ->pluck('count', 'fund_source'),
            'by_procurement_mode' => PurchaseRequest::select('procurement_mode', DB::raw('count(*) as count'))
                ->groupBy('procurement_mode')
                ->get()
                ->pluck('count', 'procurement_mode'),
        ];
    }

    public function calculateTotalAmount(int $id): float
    {
        $pr = PurchaseRequest::findOrFail($id);
        $total = $pr->items()->sum('total_cost');
        $pr->update(['total_amount' => $total]);
        return $total;
    }
}
