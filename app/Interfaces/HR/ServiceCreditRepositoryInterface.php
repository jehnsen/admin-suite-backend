<?php

namespace App\Interfaces\HR;

use App\Models\ServiceCredit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ServiceCreditRepositoryInterface
{
    /**
     * Find service credit by ID.
     */
    public function findById(int $id): ?ServiceCredit;

    /**
     * Create a new service credit.
     */
    public function create(array $data): ServiceCredit;

    /**
     * Update service credit.
     */
    public function update(int $id, array $data): ServiceCredit;

    /**
     * Delete service credit (soft delete).
     */
    public function delete(int $id): bool;

    /**
     * Get service credits for a specific employee.
     */
    public function getByEmployee(int $employeeId, array $filters = []): Collection;

    /**
     * Get all service credits with pagination and filters.
     */
    public function getAllWithPagination(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get available credits for an employee (approved, not expired, balance > 0).
     * Sorted by work_date (oldest first) for FIFO application.
     */
    public function getAvailableCredits(int $employeeId): Collection;

    /**
     * Get service credits pending approval.
     */
    public function getPendingApproval(): Collection;

    /**
     * Get service credits expiring soon.
     */
    public function getExpiringSoon(int $daysThreshold = 30): Collection;

    /**
     * Get total available balance for an employee.
     */
    public function getTotalAvailableBalance(int $employeeId): float;

    /**
     * Get earliest available credit for an employee.
     * Used for FIFO credit application.
     */
    public function getEarliestAvailableCredit(int $employeeId): ?ServiceCredit;

    /**
     * Deduct credits from a specific service credit.
     */
    public function deductCredits(int $creditId, float $amount): ServiceCredit;

    /**
     * Get credit summary for an employee.
     */
    public function getCreditSummary(int $employeeId): array;
}
