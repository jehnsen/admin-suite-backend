<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendanceRecordPolicy
{
    /**
     * Determine if user can view any attendance records.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_attendance')
            ? Response::allow()
            : Response::deny('You do not have permission to view attendance records.');
    }

    /**
     * Determine if user can view specific attendance record.
     */
    public function view(User $user, AttendanceRecord $record): Response
    {
        if (!$user->hasPermissionTo('view_attendance')) {
            return Response::deny('You do not have permission to view attendance records.');
        }

        // Can view own record or if has permission to view all
        if ($user->employee && $user->employee->id === $record->employee_id) {
            return Response::allow();
        }

        return Response::allow();
    }

    /**
     * Determine if user can create attendance records.
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_attendance')
            ? Response::allow()
            : Response::deny('You do not have permission to create attendance records.');
    }

    /**
     * Determine if user can update attendance record.
     */
    public function update(User $user, AttendanceRecord $record): Response
    {
        if (!$user->hasPermissionTo('edit_attendance')) {
            return Response::deny('You do not have permission to edit attendance records.');
        }

        // Cannot edit approved records
        if ($record->approved_at) {
            return Response::deny('Cannot edit approved attendance records.');
        }

        return Response::allow();
    }

    /**
     * Determine if user can delete attendance record.
     */
    public function delete(User $user, AttendanceRecord $record): Response
    {
        if (!$user->hasPermissionTo('edit_attendance')) {
            return Response::deny('You do not have permission to delete attendance records.');
        }

        // Cannot delete approved records
        if ($record->approved_at) {
            return Response::deny('Cannot delete approved attendance records.');
        }

        return Response::allow();
    }

    /**
     * Determine if user can approve attendance.
     */
    public function approve(User $user, AttendanceRecord $record): Response
    {
        return $user->hasPermissionTo('approve_attendance')
            ? Response::allow()
            : Response::deny('You do not have permission to approve attendance records.');
    }

    /**
     * Determine if user can upload CSV.
     */
    public function uploadCSV(User $user): Response
    {
        return $user->hasPermissionTo('upload_attendance_csv')
            ? Response::allow()
            : Response::deny('You do not have permission to upload attendance CSV files.');
    }

    /**
     * Determine if user can export attendance.
     */
    public function export(User $user): Response
    {
        return $user->hasPermissionTo('export_attendance')
            ? Response::allow()
            : Response::deny('You do not have permission to export attendance records.');
    }

    /**
     * Determine if user can manage attendance settings.
     */
    public function manageSettings(User $user): Response
    {
        return $user->hasPermissionTo('manage_attendance_settings')
            ? Response::allow()
            : Response::deny('You do not have permission to manage attendance settings.');
    }
}
