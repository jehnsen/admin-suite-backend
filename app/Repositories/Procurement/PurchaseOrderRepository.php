<?php

namespace App\Repositories\Procurement;

use App\Interfaces\Procurement\PurchaseOrderRepositoryInterface;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function getAllPurchaseOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseOrder::with(['purchaseRequest', 'supplier', 'preparedBy', 'items']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['fund_source'])) {
            $query->where('fund_source', $filters['fund_source']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('po_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('po_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('po_number', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('supplier_name', 'LIKE', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('po_date', 'desc')->paginate($perPage);
    }

    public function getPurchaseOrderById(int $id): ?PurchaseOrder
    {
        return PurchaseOrder::with([
            'purchaseRequest',
            'quotation',
            'supplier',
            'preparedBy',
            'approvedBy',
            'items',
            'deliveries'
        ])->find($id);
    }

    public function getPurchaseOrderByNumber(string $poNumber): ?PurchaseOrder
    {
        return PurchaseOrder::with('items')->where('po_number', $poNumber)->first();
    }

    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $po = PurchaseOrder::create($data);

            // Create PO items
            foreach ($items as $index => $item) {
                $item['purchase_order_id'] = $po->id;
                $item['item_number'] = $index + 1;
                $item['quantity_remaining'] = $item['quantity_ordered'];
                PurchaseOrderItem::create($item);
            }

            return $po->fresh('items');
        });
    }

    public function updatePurchaseOrder(int $id, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($id, $data) {
            $po = PurchaseOrder::findOrFail($id);

            $items = $data['items'] ?? null;
            unset($data['items']);

            $po->update($data);

            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $po->items()->delete();

                // Create new items
                foreach ($items as $index => $item) {
                    $item['purchase_order_id'] = $po->id;
                    $item['item_number'] = $index + 1;
                    $item['quantity_remaining'] = $item['quantity_ordered'];
                    PurchaseOrderItem::create($item);
                }
            }

            return $po->fresh('items');
        });
    }

    public function deletePurchaseOrder(int $id): bool
    {
        $po = PurchaseOrder::findOrFail($id);
        return $po->delete();
    }

    public function getPendingPurchaseOrders(int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseOrder::with(['supplier', 'preparedBy', 'items'])
            ->where('status', 'Pending')
            ->orderBy('po_date', 'desc')
            ->paginate($perPage);
    }

    public function approvePurchaseOrder(int $id, int $approvedBy): PurchaseOrder
    {
        $po = PurchaseOrder::findOrFail($id);

        $po->update([
            'status' => 'Approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $po->fresh();
    }

    public function updateStatus(int $id, string $status): PurchaseOrder
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->update(['status' => $status]);
        return $po->fresh();
    }

    public function getBySupplier(int $supplierId, int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseOrder::with('items')
            ->where('supplier_id', $supplierId)
            ->orderBy('po_date', 'desc')
            ->paginate($perPage);
    }

    public function getPurchaseOrderStatistics(): array
    {
        return [
            'total_pos' => PurchaseOrder::count(),
            'pending' => PurchaseOrder::where('status', 'Pending')->count(),
            'approved' => PurchaseOrder::where('status', 'Approved')->count(),
            'completed' => PurchaseOrder::where('status', 'Completed')->count(),
            'total_amount' => PurchaseOrder::sum('total_amount'),
            'by_fund_source' => PurchaseOrder::select('fund_source', DB::raw('count(*) as count, sum(total_amount) as total'))
                ->groupBy('fund_source')
                ->get()
                ->keyBy('fund_source'),
        ];
    }

    public function updateDeliveryStatus(int $poItemId, int $quantityDelivered): bool
    {
        $poItem = PurchaseOrderItem::findOrFail($poItemId);

        $poItem->quantity_delivered += $quantityDelivered;
        $poItem->save(); // This triggers the boot method which updates status

        // Check if PO is fully delivered
        $po = $poItem->purchaseOrder;
        if ($po->isFullyDelivered()) {
            $po->update(['status' => 'Fully Delivered']);
        } elseif ($po->items()->where('quantity_delivered', '>', 0)->exists()) {
            $po->update(['status' => 'Partially Delivered']);
        }

        return true;
    }
}
