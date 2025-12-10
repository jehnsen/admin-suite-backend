<?php

namespace App\Interfaces\Procurement;

use App\Models\PurchaseRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface PurchaseRequestRepositoryInterface
{
    /**
     * Get all purchase requests with optional filters and pagination
     */
    public function getAllPurchaseRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single purchase request by ID with items
     */
    public function getPurchaseRequestById(int $id): ?PurchaseRequest;

    /**
     * Get purchase request by PR number
     */
    public function getPurchaseRequestByNumber(string $prNumber): ?PurchaseRequest;

    /**
     * Create a new purchase request with items
     */
    public function createPurchaseRequest(array $data): PurchaseRequest;

    /**
     * Update a purchase request
     */
    public function updatePurchaseRequest(int $id, array $data): PurchaseRequest;

    /**
     * Delete a purchase request (soft delete)
     */
    public function deletePurchaseRequest(int $id): bool;

    /**
     * Get pending purchase requests
     */
    public function getPendingPurchaseRequests(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get approved purchase requests ready for quotation
     */
    public function getApprovedPurchaseRequests(int $perPage = 15): LengthAwarePaginator;

    /**
     * Update purchase request status
     */
    public function updateStatus(int $id, string $status, array $metadata = []): PurchaseRequest;

    /**
     * Get purchase requests by requestor
     */
    public function getByRequestor(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get purchase request statistics
     */
    public function getPurchaseRequestStatistics(): array;

    /**
     * Calculate total amount from items
     */
    public function calculateTotalAmount(int $id): float;
}
