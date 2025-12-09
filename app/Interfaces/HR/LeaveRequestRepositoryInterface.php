<?php

namespace App\Interfaces\HR;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveRequestRepositoryInterface
{
    /**
     * Get all leave requests with optional filtering and pagination.
     */
    public function getAllLeaveRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find leave request by ID.
     */
    public function findLeaveRequestById(int $id): ?LeaveRequest;

    /**
     * Create a new leave request.
     */
    public function createLeaveRequest(array $data): LeaveRequest;

    /**
     * Update leave request.
     */
    public function updateLeaveRequest(int $id, array $data): LeaveRequest;

    /**
     * Delete leave request.
     */
    public function deleteLeaveRequest(int $id): bool;

    /**
     * Get leave requests by employee ID.
     */
    public function getLeaveRequestsByEmployee(int $employeeId): Collection;

    /**
     * Get pending leave requests.
     */
    public function getPendingLeaveRequests(): Collection;

    /**
     * Get approved leave requests.
     */
    public function getApprovedLeaveRequests(): Collection;

    /**
     * Get leave requests by status.
     */
    public function getLeaveRequestsByStatus(string $status): Collection;

    /**
     * Get leave requests by leave type.
     */
    public function getLeaveRequestsByType(string $leaveType): Collection;

    /**
     * Get leave requests by date range.
     */
    public function getLeaveRequestsByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Update leave request status.
     */
    public function updateLeaveRequestStatus(int $id, string $status, array $additionalData = []): LeaveRequest;

    /**
     * Get leave statistics for employee.
     */
    public function getLeaveStatisticsByEmployee(int $employeeId): array;
}
