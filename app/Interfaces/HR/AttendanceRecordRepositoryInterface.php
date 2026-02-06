<?php

namespace App\Interfaces\HR;

use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AttendanceRecordRepositoryInterface
{
    /**
     * Find attendance record by ID.
     */
    public function findById(int $id): ?AttendanceRecord;

    /**
     * Create a new attendance record.
     */
    public function create(array $data): AttendanceRecord;

    /**
     * Update attendance record.
     */
    public function update(int $id, array $data): AttendanceRecord;

    /**
     * Delete attendance record (soft delete).
     */
    public function delete(int $id): bool;

    /**
     * Get attendance records for a specific employee.
     */
    public function getByEmployee(int $employeeId, array $filters = []): Collection;

    /**
     * Get attendance records for a date range.
     */
    public function getByDateRange(int $employeeId, string $startDate, string $endDate): Collection;

    /**
     * Get attendance records for a specific month.
     */
    public function getByMonth(int $employeeId, int $year, int $month): Collection;

    /**
     * Get all attendance records with pagination and filters.
     */
    public function getAllWithPagination(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find existing attendance record for employee and date.
     * Used to prevent duplicates.
     */
    public function findExisting(int $employeeId, string $date): ?AttendanceRecord;

    /**
     * Get attendance records pending approval.
     */
    public function getPendingApproval(array $filters = []): Collection;

    /**
     * Get attendance records with undertime.
     */
    public function getWithUndertime(int $employeeId, string $startDate, string $endDate): Collection;

    /**
     * Bulk create attendance records.
     * Returns count of successfully created records.
     */
    public function bulkCreate(array $records): int;

    /**
     * Get attendance summary for an employee in a specific month.
     */
    public function getAttendanceSummary(int $employeeId, int $year, int $month): array;

    /**
     * Get team attendance statistics for a specific date.
     */
    public function getTeamAttendanceStats(array $employeeIds, string $date): array;
}
