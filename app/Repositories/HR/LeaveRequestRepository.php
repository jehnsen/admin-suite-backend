<?php

namespace App\Repositories\HR;

use App\Interfaces\HR\LeaveRequestRepositoryInterface;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveRequestRepository implements LeaveRequestRepositoryInterface
{
    /**
     * Get all leave requests with optional filtering and pagination.
     */
    public function getAllLeaveRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LeaveRequest::with(['employee', 'recommender', 'approver']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['leave_type'])) {
            $query->where('leave_type', $filters['leave_type']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Find leave request by ID.
     */
    public function findLeaveRequestById(int $id): ?LeaveRequest
    {
        return LeaveRequest::with([
            'employee',
            'recommender',
            'approver',
            'disapprover'
        ])->find($id);
    }

    /**
     * Create a new leave request.
     */
    public function createLeaveRequest(array $data): LeaveRequest
    {
        return LeaveRequest::create($data);
    }

    /**
     * Update leave request.
     */
    public function updateLeaveRequest(int $id, array $data): LeaveRequest
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $leaveRequest->update($data);
        return $leaveRequest->fresh();
    }

    /**
     * Delete leave request.
     */
    public function deleteLeaveRequest(int $id): bool
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        return $leaveRequest->delete();
    }

    /**
     * Get leave requests by employee ID.
     */
    public function getLeaveRequestsByEmployee(int $employeeId): Collection
    {
        return LeaveRequest::where('employee_id', $employeeId)
                          ->orderBy('start_date', 'desc')
                          ->get();
    }

    /**
     * Get pending leave requests.
     */
    public function getPendingLeaveRequests(): Collection
    {
        return LeaveRequest::pending()
                          ->with('employee')
                          ->orderBy('created_at', 'asc')
                          ->get();
    }

    /**
     * Get approved leave requests.
     */
    public function getApprovedLeaveRequests(): Collection
    {
        return LeaveRequest::approved()
                          ->with('employee')
                          ->orderBy('start_date', 'desc')
                          ->get();
    }

    /**
     * Get leave requests by status.
     */
    public function getLeaveRequestsByStatus(string $status): Collection
    {
        return LeaveRequest::where('status', $status)
                          ->with('employee')
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Get leave requests by leave type.
     */
    public function getLeaveRequestsByType(string $leaveType): Collection
    {
        return LeaveRequest::byLeaveType($leaveType)
                          ->with('employee')
                          ->orderBy('start_date', 'desc')
                          ->get();
    }

    /**
     * Get leave requests by date range.
     */
    public function getLeaveRequestsByDateRange(string $startDate, string $endDate): Collection
    {
        return LeaveRequest::dateRange($startDate, $endDate)
                          ->with('employee')
                          ->orderBy('start_date', 'asc')
                          ->get();
    }

    /**
     * Update leave request status.
     */
    public function updateLeaveRequestStatus(int $id, string $status, array $additionalData = []): LeaveRequest
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        $updateData = array_merge(['status' => $status], $additionalData);
        $leaveRequest->update($updateData);

        return $leaveRequest->fresh();
    }

    /**
     * Get leave statistics for employee.
     */
    public function getLeaveStatisticsByEmployee(int $employeeId): array
    {
        $currentYear = now()->year;

        return [
            'total_leaves' => LeaveRequest::where('employee_id', $employeeId)->count(),
            'approved_leaves' => LeaveRequest::where('employee_id', $employeeId)
                                            ->approved()
                                            ->count(),
            'pending_leaves' => LeaveRequest::where('employee_id', $employeeId)
                                           ->pending()
                                           ->count(),
            'current_year_leaves' => LeaveRequest::where('employee_id', $employeeId)
                                                 ->whereYear('start_date', $currentYear)
                                                 ->approved()
                                                 ->count(),
            'total_days_used' => LeaveRequest::where('employee_id', $employeeId)
                                            ->approved()
                                            ->whereYear('start_date', $currentYear)
                                            ->sum('days_requested'),
        ];
    }
}
