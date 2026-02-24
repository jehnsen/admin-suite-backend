<?php

namespace App\Services\Procurement;

use App\Interfaces\Procurement\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    protected $supplierRepository;

    public function __construct(SupplierRepositoryInterface $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * Get all suppliers with filters
     */
    public function getAllSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->supplierRepository->getAllSuppliers($filters, $perPage);
    }

    /**
     * Get supplier by ID
     */
    public function getSupplierById(int $id): ?Supplier
    {
        return $this->supplierRepository->getSupplierById($id);
    }

    /**
     * Create new supplier
     */
    public function createSupplier(array $data): Supplier
    {
        // Generate supplier code if not provided
        if (empty($data['supplier_code'])) {
            $data['supplier_code'] = $this->generateSupplierCode();
        }

        return $this->supplierRepository->createSupplier($data);
    }

    /**
     * Update supplier
     */
    public function updateSupplier(int $id, array $data): Supplier
    {
        return $this->supplierRepository->updateSupplier($id, $data);
    }

    /**
     * Delete supplier
     */
    public function deleteSupplier(int $id): bool
    {
        // Check if supplier has active purchase orders
        $supplier = $this->supplierRepository->getSupplierById($id);

        if ($supplier->purchaseOrders()->whereIn('status', ['Pending', 'Approved', 'Sent to Supplier'])->exists()) {
            throw new \Exception('Cannot delete supplier with active purchase orders.');
        }

        return $this->supplierRepository->deleteSupplier($id);
    }

    /**
     * Get active suppliers only
     */
    public function getActiveSuppliers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->supplierRepository->getActiveSuppliers($perPage);
    }

    /**
     * Search suppliers
     */
    public function searchSuppliers(string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        return $this->supplierRepository->searchSuppliers($keyword, $perPage);
    }

    /**
     * Get supplier statistics
     */
    public function getSupplierStatistics(): array
    {
        return $this->supplierRepository->getSupplierStatistics();
    }

    /**
     * Update supplier performance after PO completion
     */
    public function updatePerformance(int $id, float $rating, float $amount): Supplier
    {
        return $this->supplierRepository->updatePerformanceMetrics($id, $rating, $amount);
    }

    /**
     * Generate unique supplier code
     */
    private function generateSupplierCode(): string
    {
        $year = date('Y');
        $lastSupplier = Supplier::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastSupplier ? ((int) substr($lastSupplier->supplier_code, -4)) + 1 : 1;

        return 'SUP-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
