<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveRequestPolicy
{
    /**
     * Determine if user can view any leave requests.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_leave_requests')
            ? Response::allow()
            : Response::deny('You do not have permission to view leave requests.');
    }

    /**
     * Determine if user can view a specific leave request.
     */
    public function view(User $user, LeaveRequest $leaveRequest): Response
    {
        // Super Admin, School Head, Admin Officer can view all
        if ($user->hasAnyRole(['Super Admin', 'School Head', 'Admin Officer'])) {
            return Response::allow();
        }

        // User can view their own leave requests
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return Response::allow();
        }

        return Response::deny('You can only view your own leave requests.');
    }

    /**
     * Determine if user can create leave requests.
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_leave_request')
            ? Response::allow()
            : Response::deny('You do not have permission to create leave requests.');
    }

    /**
     * Determine if user can update the leave request.
     */
    public function update(User $user, LeaveRequest $leaveRequest): Response
    {
        // Cannot update approved/disapproved requests
        if (in_array($leaveRequest->status, ['Approved', 'Disapproved'])) {
            return Response::deny('Cannot update a finalized leave request.');
        }

        // User can update their own pending requests
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return Response::allow();
        }

        return Response::deny('You can only update your own leave requests.');
    }

    /**
     * Determine if user can recommend the leave request.
     */
    public function recommend(User $user, LeaveRequest $leaveRequest): Response
    {
        if (!$user->hasPermissionTo('recommend_leave')) {
            return Response::deny('You do not have permission to recommend leaves.');
        }

        // Cannot recommend own leave
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return Response::deny('You cannot recommend your own leave request.');
        }

        // Cannot recommend already processed request
        if (in_array($leaveRequest->status, ['Approved', 'Disapproved'])) {
            return Response::deny('This leave request has already been processed.');
        }

        return Response::allow();
    }

    /**
     * Determine if user can approve the leave request.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): Response
    {
        if (!$user->hasPermissionTo('approve_leave')) {
            return Response::deny('You do not have permission to approve leaves.');
        }

        // Cannot approve own leave
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return Response::deny('You cannot approve your own leave request.');
        }

        // Cannot approve already approved request
        if ($leaveRequest->status === 'Approved') {
            return Response::deny('This leave request is already approved.');
        }

        return Response::allow();
    }

    /**
     * Determine if user can disapprove the leave request.
     */
    public function disapprove(User $user, LeaveRequest $leaveRequest): Response
    {
        if (!$user->hasPermissionTo('reject_leave')) {
            return Response::deny('You do not have permission to reject leaves.');
        }

        // Cannot disapprove own leave
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            return Response::deny('You cannot disapprove your own leave request.');
        }

        // Cannot disapprove already disapproved request
        if ($leaveRequest->status === 'Disapproved') {
            return Response::deny('This leave request is already disapproved.');
        }

        return Response::allow();
    }

    /**
     * Determine if user can cancel the leave request.
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): Response
    {
        // Only the employee who created the request can cancel it
        if ($user->employee && $user->employee->id === $leaveRequest->employee_id) {
            // Cannot cancel approved/disapproved requests
            if (in_array($leaveRequest->status, ['Approved', 'Disapproved'])) {
                return Response::deny('Cannot cancel a finalized leave request.');
            }
            return Response::allow();
        }

        return Response::deny('You can only cancel your own leave requests.');
    }
}
