<?php

namespace App\Repositories\Procurement;

use App\Interfaces\Procurement\DeliveryRepositoryInterface;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DeliveryRepository implements DeliveryRepositoryInterface
{
    public function getAllDeliveries(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Delivery::with(['purchaseOrder', 'supplier', 'receivedBy', 'items']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $filters['purchase_order_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('delivery_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('delivery_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('delivery_date', 'desc')->paginate($perPage);
    }

    public function getDeliveryById(int $id): ?Delivery
    {
        return Delivery::with([
            'purchaseOrder.items',
            'supplier',
            'receivedBy',
            'inspectedBy',
            'acceptedBy',
            'items.purchaseOrderItem'
        ])->find($id);
    }

    public function getDeliveriesByPurchaseOrder(int $poId, int $perPage = 15): LengthAwarePaginator
    {
        return Delivery::with(['supplier', 'receivedBy', 'items'])
            ->where('purchase_order_id', $poId)
            ->orderBy('delivery_date', 'desc')
            ->paginate($perPage);
    }

    public function createDelivery(array $data): Delivery
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $delivery = Delivery::create($data);

            // Create delivery items
            foreach ($items as $index => $item) {
                $item['delivery_id'] = $delivery->id;
                $item['item_number'] = $index + 1;
                DeliveryItem::create($item);
            }

            return $delivery->fresh('items');
        });
    }

    public function updateDelivery(int $id, array $data): Delivery
    {
        return DB::transaction(function () use ($id, $data) {
            $delivery = Delivery::findOrFail($id);

            $items = $data['items'] ?? null;
            unset($data['items']);

            $delivery->update($data);

            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $delivery->items()->delete();

                // Create new items
                foreach ($items as $index => $item) {
                    $item['delivery_id'] = $delivery->id;
                    $item['item_number'] = $index + 1;
                    DeliveryItem::create($item);
                }
            }

            return $delivery->fresh('items');
        });
    }

    public function deleteDelivery(int $id): bool
    {
        $delivery = Delivery::findOrFail($id);
        return $delivery->delete();
    }

    public function getPendingDeliveries(int $perPage = 15): LengthAwarePaginator
    {
        return Delivery::with(['purchaseOrder', 'supplier', 'receivedBy', 'items'])
            ->where('status', 'Pending Inspection')
            ->orderBy('delivery_date', 'desc')
            ->paginate($perPage);
    }

    public function inspectDelivery(int $id, array $inspectionData): Delivery
    {
        $delivery = Delivery::findOrFail($id);

        $delivery->update([
            'status' => 'Under Inspection',
            'inspected_by' => $inspectionData['inspected_by'],
            'inspected_at' => now(),
            'inspection_result' => $inspectionData['inspection_result'],
            'inspection_remarks' => $inspectionData['inspection_remarks'] ?? null,
        ]);

        return $delivery->fresh();
    }

    public function acceptDelivery(int $id, int $acceptedBy): Delivery
    {
        $delivery = Delivery::findOrFail($id);

        $delivery->update([
            'status' => 'Accepted',
            'accepted_by' => $acceptedBy,
            'accepted_at' => now(),
        ]);

        return $delivery->fresh();
    }

    public function rejectDelivery(int $id, string $reason): Delivery
    {
        $delivery = Delivery::findOrFail($id);

        $delivery->update([
            'status' => 'Rejected',
            'inspection_result' => 'Failed',
            'inspection_remarks' => $reason,
        ]);

        return $delivery->fresh();
    }

    public function getDeliveryStatistics(): array
    {
        return [
            'total_deliveries' => Delivery::count(),
            'pending_inspection' => Delivery::where('status', 'Pending Inspection')->count(),
            'under_inspection' => Delivery::where('status', 'Under Inspection')->count(),
            'accepted' => Delivery::where('status', 'Accepted')->count(),
            'rejected' => Delivery::where('status', 'Rejected')->count(),
            'by_condition' => Delivery::select('condition', DB::raw('count(*) as count'))
                ->groupBy('condition')
                ->get()
                ->pluck('count', 'condition'),
        ];
    }
}
