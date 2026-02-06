<?php

namespace App\Interfaces\HR;

use App\Models\ServiceCreditOffset;
use Illuminate\Database\Eloquent\Collection;

interface ServiceCreditOffsetRepositoryInterface
{
    /**
     * Find service credit offset by ID.
     */
    public function findById(int $id): ?ServiceCreditOffset;

    /**
     * Create a new service credit offset.
     */
    public function create(array $data): ServiceCreditOffset;

    /**
     * Update service credit offset.
     */
    public function update(int $id, array $data): ServiceCreditOffset;

    /**
     * Get offsets for a specific service credit.
     */
    public function getByServiceCredit(int $creditId): Collection;

    /**
     * Get offsets for a specific employee.
     */
    public function getByEmployee(int $employeeId): Collection;

    /**
     * Get offsets for a specific attendance record.
     */
    public function getByAttendanceRecord(int $recordId): Collection;
}
