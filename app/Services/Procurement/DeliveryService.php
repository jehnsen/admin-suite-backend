<?php

namespace App\Services\Procurement;

use App\Interfaces\Procurement\DeliveryRepositoryInterface;
use App\Interfaces\Procurement\PurchaseOrderRepositoryInterface;
use App\Interfaces\Procurement\SupplierRepositoryInterface;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\InventoryItem;
use App\Services\Inventory\AssetTaggingService;
use App\Services\Inventory\StockCardService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    protected $deliveryRepository;
    protected $poRepository;
    protected $supplierRepository;
    protected $stockCardService;
    protected $assetTaggingService;

    public function __construct(
        DeliveryRepositoryInterface $deliveryRepository,
        PurchaseOrderRepositoryInterface $poRepository,
        SupplierRepositoryInterface $supplierRepository,
        StockCardService $stockCardService,
        AssetTaggingService $assetTaggingService
    ) {
        $this->deliveryRepository = $deliveryRepository;
        $this->poRepository = $poRepository;
        $this->supplierRepository = $supplierRepository;
        $this->stockCardService = $stockCardService;
        $this->assetTaggingService = $assetTaggingService;
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

            // Process each delivery item to create inventory items and stock cards
            foreach ($delivery->items as $deliveryItem) {
                if ($deliveryItem->quantity_accepted > 0) {
                    $this->processDeliveryItem($deliveryItem, $acceptedBy);
                }
            }

            // Update PO status to completed if fully delivered
            $po = $delivery->purchaseOrder;
            if ($po->isFullyDelivered()) {
                $this->poRepository->updateStatus($po->id, 'Completed');
            }

            return $delivery->fresh(['items']);
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
        $lastDelivery = Delivery::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastDelivery ? ((int) substr($lastDelivery->delivery_receipt_number, -4)) + 1 : 1;

        return 'DR-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Process delivery item to create inventory items and stock cards
     */
    private function processDeliveryItem(DeliveryItem $deliveryItem, int $acceptedBy): void
    {
        $poItem = $deliveryItem->purchaseOrderItem;

        if (!$poItem) {
            throw new \Exception("Purchase order item not found for delivery item {$deliveryItem->id}");
        }

        // Determine category from PO item
        $category = $this->determineCategoryFromPOItem($poItem);
        $isEquipment = $this->assetTaggingService->requiresAssetTagging($category);

        if ($isEquipment) {
            // Create individual InventoryItems for each unit of equipment
            for ($i = 0; $i < $deliveryItem->quantity_accepted; $i++) {
                $inventoryItem = $this->assetTaggingService->createInventoryItemFromDeliveryItem(
                    $deliveryItem,
                    1 // Quantity = 1 per equipment item
                );

                // Create stock card for this unit
                $this->createStockCardForItem($inventoryItem, $deliveryItem, $acceptedBy, 1);
            }
        } else {
            // Create/update single InventoryItem for supplies
            $inventoryItem = $this->assetTaggingService->createInventoryItemFromDeliveryItem(
                $deliveryItem,
                $deliveryItem->quantity_accepted
            );

            // Create stock card for total quantity
            $this->createStockCardForItem(
                $inventoryItem,
                $deliveryItem,
                $acceptedBy,
                $deliveryItem->quantity_accepted
            );
        }
    }

    /**
     * Create stock card entry for inventory item
     */
    private function createStockCardForItem(
        InventoryItem $inventoryItem,
        DeliveryItem $deliveryItem,
        int $acceptedBy,
        int $quantity
    ): void {
        $delivery = $deliveryItem->delivery;
        $poItem = $deliveryItem->purchaseOrderItem;

        $stockCardData = [
            'inventory_item_id' => $inventoryItem->id,
            'transaction_date' => $delivery->delivery_date ?? now(),
            'reference_number' => $delivery->delivery_receipt_number,
            'transaction_type' => 'Stock In',
            'source_destination' => $delivery->supplier->supplier_name ?? 'Supplier',
            'quantity_in' => $quantity,
            'quantity_out' => 0,
            'unit_cost' => $poItem->unit_price ?? 0,
            'delivery_id' => $delivery->id,
            'purchase_order_id' => $delivery->purchase_order_id,
            'processed_by' => $acceptedBy,
            'remarks' => "Stock in from delivery: {$delivery->delivery_receipt_number}",
        ];

        $this->stockCardService->recordStockIn($stockCardData);
    }

    /**
     * Determine category from Purchase Order Item
     */
    private function determineCategoryFromPOItem($poItem): string
    {
        if (!$poItem) {
            return 'General Supplies';
        }

        $description = strtolower($poItem->item_description ?? '');

        // Try to match common keywords to categories
        if (str_contains($description, 'computer') || str_contains($description, 'printer') ||
            str_contains($description, 'laptop') || str_contains($description, 'scanner')) {
            return 'ICT Equipment';
        }

        if (str_contains($description, 'desk') || str_contains($description, 'chair') ||
            str_contains($description, 'cabinet') || str_contains($description, 'table')) {
            return 'Furniture';
        }

        if (str_contains($description, 'paper') || str_contains($description, 'pen') ||
            str_contains($description, 'marker') || str_contains($description, 'folder')) {
            return 'Office Supplies';
        }

        if (str_contains($description, 'laboratory') || str_contains($description, 'science')) {
            return 'Laboratory Equipment';
        }

        if (str_contains($description, 'sports') || str_contains($description, 'ball') ||
            str_contains($description, 'athletic')) {
            return 'Sports Equipment';
        }

        return 'General Supplies';
    }
}
