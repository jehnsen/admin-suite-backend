<?php

namespace App\Interfaces\Procurement;

use App\Models\Delivery;
use Illuminate\Pagination\LengthAwarePaginator;

interface DeliveryRepositoryInterface
{
    /**
     * Get all deliveries with optional filters and pagination
     */
    public function getAllDeliveries(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single delivery by ID with items
     */
    public function getDeliveryById(int $id): ?Delivery;

    /**
     * Get deliveries by purchase order ID
     */
    public function getDeliveriesByPurchaseOrder(int $poId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new delivery with items
     */
    public function createDelivery(array $data): Delivery;

    /**
     * Update a delivery
     */
    public function updateDelivery(int $id, array $data): Delivery;

    /**
     * Delete a delivery (soft delete)
     */
    public function deleteDelivery(int $id): bool;

    /**
     * Get pending deliveries for inspection
     */
    public function getPendingDeliveries(int $perPage = 15): LengthAwarePaginator;

    /**
     * Inspect a delivery
     */
    public function inspectDelivery(int $id, array $inspectionData): Delivery;

    /**
     * Accept a delivery
     */
    public function acceptDelivery(int $id, int $acceptedBy): Delivery;

    /**
     * Reject a delivery
     */
    public function rejectDelivery(int $id, string $reason): Delivery;

    /**
     * Get delivery statistics
     */
    public function getDeliveryStatistics(): array;
}
