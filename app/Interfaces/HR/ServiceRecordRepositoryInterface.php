<?php

namespace App\Interfaces\HR;

use App\Models\ServiceRecord;
use Illuminate\Database\Eloquent\Collection;

interface ServiceRecordRepositoryInterface
{
    /**
     * Get all service records for an employee.
     */
    public function getServiceRecordsByEmployee(int $employeeId): Collection;

    /**
     * Find service record by ID.
     */
    public function findServiceRecordById(int $id): ?ServiceRecord;

    /**
     * Create a new service record.
     */
    public function createServiceRecord(array $data): ServiceRecord;

    /**
     * Update service record.
     */
    public function updateServiceRecord(int $id, array $data): ServiceRecord;

    /**
     * Delete service record.
     */
    public function deleteServiceRecord(int $id): bool;

    /**
     * Get current service record for employee.
     */
    public function getCurrentServiceRecord(int $employeeId): ?ServiceRecord;

    /**
     * Get service records by action type.
     */
    public function getServiceRecordsByActionType(string $actionType): Collection;

    /**
     * Close current service record (set date_to).
     */
    public function closeCurrentServiceRecord(int $employeeId, string $dateTo): ?ServiceRecord;

    /**
     * Get service history for employee ordered by date.
     */
    public function getServiceHistory(int $employeeId): Collection;
}
