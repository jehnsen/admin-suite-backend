<?php

namespace App\Services\HR;

use App\Interfaces\HR\LeaveRequestRepositoryInterface;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveRequestService
{
    public function __construct(
        private LeaveRequestRepositoryInterface $leaveRequestRepository,
        private EmployeeService $employeeService
    ) {}

    /**
     * Get all leave requests with filtering.
     * Applies ownership filtering for Teacher/Staff users.
     */
    public function getAllLeaveRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = auth()->user();

        // Teachers/Staff can only see their own leave requests
        if ($user && $user->hasRole('Teacher/Staff') && $user->employee) {
            $filters['employee_id'] = $user->employee->id;
        }

        return $this->leaveRequestRepository->getAllLeaveRequests($filters, $perPage);
    }

    /**
     * Find leave request by ID.
     */
    public function findLeaveRequestById(int $id): ?LeaveRequest
    {
        return $this->leaveRequestRepository->findLeaveRequestById($id);
    }

    /**
     * Create a new leave request.
     * Business Logic: Calculate days, validate credits for VL/SL.
     */
    public function createLeaveRequest(array $data): LeaveRequest
    {
        // Calculate days if not provided
        if (!isset($data['days_requested'])) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            $data['days_requested'] = $this->calculateWorkingDays($startDate, $endDate);
        }

        // Validate leave credits for Vacation/Sick leave
        if (in_array($data['leave_type'], ['Vacation Leave', 'Sick Leave'])) {
            $this->validateLeaveCredits($data['employee_id'], $data['leave_type'], $data['days_requested']);
        }

        // Check for overlapping leave requests
        $this->checkOverlappingLeaves($data['employee_id'], $data['start_date'], $data['end_date']);

        return $this->leaveRequestRepository->createLeaveRequest($data);
    }

    /**
     * Update leave request.
     */
    public function updateLeaveRequest(int $id, array $data): LeaveRequest
    {
        return $this->leaveRequestRepository->updateLeaveRequest($id, $data);
    }

    /**
     * Recommend leave request.
     */
    public function recommendLeaveRequest(int $id, int $recommendedBy, ?string $remarks = null): LeaveRequest
    {
        return $this->leaveRequestRepository->updateLeaveRequestStatus($id, 'Recommended', [
            'recommended_by' => $recommendedBy,
            'recommended_at' => now(),
            'recommendation_remarks' => $remarks,
        ]);
    }

    /**
     * Approve leave request.
     * Business Logic: Deduct leave credits from employee.
     */
    public function approveLeaveRequest(int $id, int $approvedBy, ?string $remarks = null): LeaveRequest
    {
        return DB::transaction(function () use ($id, $approvedBy, $remarks) {
            $leaveRequest = $this->leaveRequestRepository->findLeaveRequestById($id);

            if (!$leaveRequest) {
                throw new \Exception('Leave request not found.');
            }

            if ($leaveRequest->status === 'Approved') {
                throw new \Exception('Leave request already approved.');
            }

            // Deduct leave credits
            $this->employeeService->deductLeaveCredits(
                $leaveRequest->employee_id,
                $leaveRequest->leave_type,
                $leaveRequest->days_requested
            );

            // Update leave request status
            return $this->leaveRequestRepository->updateLeaveRequestStatus($id, 'Approved', [
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'approval_remarks' => $remarks,
            ]);
        });
    }

    /**
     * Disapprove leave request.
     */
    public function disapproveLeaveRequest(int $id, int $disapprovedBy, string $reason): LeaveRequest
    {
        return $this->leaveRequestRepository->updateLeaveRequestStatus($id, 'Disapproved', [
            'disapproved_by' => $disapprovedBy,
            'disapproved_at' => now(),
            'disapproval_reason' => $reason,
        ]);
    }

    /**
     * Cancel leave request.
     * Business Logic: Restore leave credits if already approved.
     */
    public function cancelLeaveRequest(int $id): LeaveRequest
    {
        return DB::transaction(function () use ($id) {
            $leaveRequest = $this->leaveRequestRepository->findLeaveRequestById($id);

            if (!$leaveRequest) {
                throw new \Exception('Leave request not found.');
            }

            if (!$leaveRequest->canBeCancelled() && $leaveRequest->status !== 'Approved') {
                throw new \Exception('Leave request cannot be cancelled.');
            }

            // If approved, restore leave credits
            if ($leaveRequest->status === 'Approved') {
                $this->employeeService->restoreLeaveCredits(
                    $leaveRequest->employee_id,
                    $leaveRequest->leave_type,
                    $leaveRequest->days_requested
                );
            }

            return $this->leaveRequestRepository->updateLeaveRequestStatus($id, 'Cancelled');
        });
    }

    /**
     * Get leave requests by employee.
     */
    public function getLeaveRequestsByEmployee(int $employeeId): Collection
    {
        return $this->leaveRequestRepository->getLeaveRequestsByEmployee($employeeId);
    }

    /**
     * Get pending leave requests.
     */
    public function getPendingLeaveRequests(): Collection
    {
        return $this->leaveRequestRepository->getPendingLeaveRequests();
    }

    /**
     * Get leave statistics for employee.
     */
    public function getLeaveStatistics(int $employeeId): array
    {
        return $this->leaveRequestRepository->getLeaveStatisticsByEmployee($employeeId);
    }

    /**
     * Calculate working days between two dates (excluding weekends).
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate): float
    {
        $days = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Only count weekdays (Monday to Friday)
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return (float) $days;
    }

    /**
     * Validate if employee has sufficient leave credits.
     */
    private function validateLeaveCredits(int $employeeId, string $leaveType, float $daysRequested): void
    {
        $employee = $this->employeeService->findEmployeeById($employeeId);

        if (!$employee) {
            throw new \Exception('Employee not found.');
        }

        if ($leaveType === 'Vacation Leave' && $employee->vacation_leave_credits < $daysRequested) {
            throw new \Exception("Insufficient vacation leave credits. Available: {$employee->vacation_leave_credits} days.");
        }

        if ($leaveType === 'Sick Leave' && $employee->sick_leave_credits < $daysRequested) {
            throw new \Exception("Insufficient sick leave credits. Available: {$employee->sick_leave_credits} days.");
        }
    }

    /**
     * Check for overlapping leave requests.
     */
    private function checkOverlappingLeaves(int $employeeId, string $startDate, string $endDate): void
    {
        $overlapping = LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['Pending', 'Recommended', 'Approved'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->exists();

        if ($overlapping) {
            throw new \Exception('Employee already has a leave request for this date range.');
        }
    }

    /**
     * Delete leave request.
     */
    public function deleteLeaveRequest(int $id): bool
    {
        return $this->leaveRequestRepository->deleteLeaveRequest($id);
    }
}
