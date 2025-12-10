<?php

namespace App\Interfaces\Procurement;

use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;

interface SupplierRepositoryInterface
{
    /**
     * Get all suppliers with optional filters and pagination
     */
    public function getAllSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single supplier by ID
     */
    public function getSupplierById(int $id): ?Supplier;

    /**
     * Get supplier by supplier code
     */
    public function getSupplierByCode(string $code): ?Supplier;

    /**
     * Create a new supplier
     */
    public function createSupplier(array $data): Supplier;

    /**
     * Update a supplier
     */
    public function updateSupplier(int $id, array $data): Supplier;

    /**
     * Delete a supplier (soft delete)
     */
    public function deleteSupplier(int $id): bool;

    /**
     * Get active suppliers only
     */
    public function getActiveSuppliers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Search suppliers by name or code
     */
    public function searchSuppliers(string $keyword, int $perPage = 15): LengthAwarePaginator;

    /**
     * Update supplier performance metrics
     */
    public function updatePerformanceMetrics(int $id, float $rating, float $amount): Supplier;

    /**
     * Get supplier statistics
     */
    public function getSupplierStatistics(): array;
}
