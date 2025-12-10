<?php

namespace App\Interfaces\Procurement;

use App\Models\PurchaseOrder;
use Illuminate\Pagination\LengthAwarePaginator;

interface PurchaseOrderRepositoryInterface
{
    /**
     * Get all purchase orders with optional filters and pagination
     */
    public function getAllPurchaseOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single purchase order by ID with items
     */
    public function getPurchaseOrderById(int $id): ?PurchaseOrder;

    /**
     * Get purchase order by PO number
     */
    public function getPurchaseOrderByNumber(string $poNumber): ?PurchaseOrder;

    /**
     * Create a new purchase order with items
     */
    public function createPurchaseOrder(array $data): PurchaseOrder;

    /**
     * Update a purchase order
     */
    public function updatePurchaseOrder(int $id, array $data): PurchaseOrder;

    /**
     * Delete a purchase order (soft delete)
     */
    public function deletePurchaseOrder(int $id): bool;

    /**
     * Get pending purchase orders
     */
    public function getPendingPurchaseOrders(int $perPage = 15): LengthAwarePaginator;

    /**
     * Approve a purchase order
     */
    public function approvePurchaseOrder(int $id, int $approvedBy): PurchaseOrder;

    /**
     * Update purchase order status
     */
    public function updateStatus(int $id, string $status): PurchaseOrder;

    /**
     * Get purchase orders by supplier
     */
    public function getBySupplier(int $supplierId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get purchase order statistics
     */
    public function getPurchaseOrderStatistics(): array;

    /**
     * Update delivery status for PO items
     */
    public function updateDeliveryStatus(int $poItemId, int $quantityDelivered): bool;
}
