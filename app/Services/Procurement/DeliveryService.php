<?php

namespace App\Services\Procurement;

use App\Interfaces\Procurement\DeliveryRepositoryInterface;
use App\Interfaces\Procurement\PurchaseOrderRepositoryInterface;
use App\Interfaces\Procurement\SupplierRepositoryInterface;
use App\Models\Delivery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    protected $deliveryRepository;
    protected $poRepository;
    protected $supplierRepository;

    public function __construct(
        DeliveryRepositoryInterface $deliveryRepository,
        PurchaseOrderRepositoryInterface $poRepository,
        SupplierRepositoryInterface $supplierRepository
    ) {
        $this->deliveryRepository = $deliveryRepository;
        $this->poRepository = $poRepository;
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * Get all deliveries with filters
     */
    public function getAllDeliveries(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->deliveryRepository->getAllDeliveries($filters, $perPage);
    }

    /**
     * Get delivery by ID
     */
    public function getDeliveryById(int $id): ?Delivery
    {
        return $this->deliveryRepository->getDeliveryById($id);
    }

    /**
     * Get deliveries by purchase order
     */
    public function getDeliveriesByPurchaseOrder(int $poId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->deliveryRepository->getDeliveriesByPurchaseOrder($poId, $perPage);
    }

    /**
     * Create new delivery
     */
    public function createDelivery(array $data): Delivery
    {
        return DB::transaction(function () use ($data) {
            // Validate PO
            $po = $this->poRepository->getPurchaseOrderById($data['purchase_order_id']);

            if (!$po || !in_array($po->status, ['Approved', 'Sent to Supplier', 'Partially Delivered'])) {
                throw new \Exception('Purchase order must be approved before receiving deliveries.');
            }

            // Generate delivery receipt number if not provided
            if (empty($data['delivery_receipt_number'])) {
                $data['delivery_receipt_number'] = $this->generateDeliveryReceiptNumber();
            }

            // Set default status
            if (empty($data['status'])) {
                $data['status'] = 'Pending Inspection';
            }

            // Create delivery
            $delivery = $this->deliveryRepository->createDelivery($data);

            // Update PO item delivery quantities
            foreach ($delivery->items as $deliveryItem) {
                $this->poRepository->updateDeliveryStatus(
                    $deliveryItem->purchase_order_item_id,
                    $deliveryItem->quantity_delivered
                );
            }

            return $delivery;
        });
    }

    /**
     * Update delivery
     */
    public function updateDelivery(int $id, array $data): Delivery
    {
        $delivery = $this->deliveryRepository->getDeliveryById($id);

        if (in_array($delivery->status, ['Accepted', 'Rejected'])) {
            throw new \Exception('Cannot update delivery that has been accepted or rejected.');
        }

        return $this->deliveryRepository->updateDelivery($id, $data);
    }

    /**
     * Delete delivery
     */
    public function deleteDelivery(int $id): bool
    {
        $delivery = $this->deliveryRepository->getDeliveryById($id);

        if ($delivery->status !== 'Pending Inspection') {
            throw new \Exception('Only pending deliveries can be deleted.');
        }

        return $this->deliveryRepository->deleteDelivery($id);
    }

    /**
     * Inspect delivery
     */
    public function inspectDelivery(int $id, array $inspectionData): Delivery
    {
        $delivery = $this->deliveryRepository->getDeliveryById($id);

        if (!$delivery->canBeInspected()) {
            throw new \Exception('Delivery cannot be inspected in current status.');
        }

        return $this->deliveryRepository->inspectDelivery($id, $inspectionData);
    }

    /**
     * Accept delivery
     */
    public function acceptDelivery(int $id, int $acceptedBy): Delivery
    {
        return DB::transaction(function () use ($id, $acceptedBy) {
            $delivery = $this->deliveryRepository->getDeliveryById($id);

            if (!$delivery->canBeAccepted()) {
                throw new \Exception('Delivery must pass inspection before acceptance.');
            }

            $delivery = $this->deliveryRepository->acceptDelivery($id, $acceptedBy);

            // Update PO status to completed if fully delivered
            $po = $delivery->purchaseOrder;
            if ($po->isFullyDelivered()) {
                $this->poRepository->updateStatus($po->id, 'Completed');
            }

            return $delivery;
        });
    }

    /**
     * Reject delivery
     */
    public function rejectDelivery(int $id, string $reason): Delivery
    {
        $delivery = $this->deliveryRepository->getDeliveryById($id);

        if (!in_array($delivery->status, ['Pending Inspection', 'Under Inspection'])) {
            throw new \Exception('Only pending or under inspection deliveries can be rejected.');
        }

        return $this->deliveryRepository->rejectDelivery($id, $reason);
    }

    /**
     * Get pending deliveries
     */
    public function getPendingDeliveries(int $perPage = 15): LengthAwarePaginator
    {
        return $this->deliveryRepository->getPendingDeliveries($perPage);
    }

    /**
     * Get delivery statistics
     */
    public function getDeliveryStatistics(): array
    {
        return $this->deliveryRepository->getDeliveryStatistics();
    }

    /**
     * Generate unique delivery receipt number
     */
    private function generateDeliveryReceiptNumber(): string
    {
        $year = date('Y');
        $lastDelivery = Delivery::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastDelivery ? ((int) substr($lastDelivery->delivery_receipt_number, -4)) + 1 : 1;

        return 'DR-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
