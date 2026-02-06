<?php

namespace App\Repositories\HR;

use App\Interfaces\HR\AttendanceRecordRepositoryInterface;
use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AttendanceRecordRepository implements AttendanceRecordRepositoryInterface
{
    /**
     * Find attendance record by ID.
     */
    public function findById(int $id): ?AttendanceRecord
    {
        return AttendanceRecord::with(['employee', 'creator', 'approver'])->find($id);
    }

    /**
     * Create a new attendance record.
     */
    public function create(array $data): AttendanceRecord
    {
        return AttendanceRecord::create($data);
    }

    /**
     * Update attendance record.
     */
    public function update(int $id, array $data): AttendanceRecord
    {
        $record = AttendanceRecord::findOrFail($id);
        $record->update($data);
        return $record->fresh(['employee', 'creator', 'approver']);
    }

    /**
     * Delete attendance record (soft delete).
     */
    public function delete(int $id): bool
    {
        $record = AttendanceRecord::findOrFail($id);
        return $record->delete();
    }

    /**
     * Get attendance records for a specific employee.
     */
    public function getByEmployee(int $employeeId, array $filters = []): Collection
    {
        $query = AttendanceRecord::where('employee_id', $employeeId)
            ->with(['employee', 'creator', 'approver']);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('attendance_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('attendance_date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('attendance_date', 'desc')->get();
    }

    /**
     * Get attendance records for a date range.
     */
    public function getByDateRange(int $employeeId, string $startDate, string $endDate): Collection
    {
        return AttendanceRecord::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'asc')
            ->get();
    }

    /**
     * Get attendance records for a specific month.
     */
    public function getByMonth(int $employeeId, int $year, int $month): Collection
    {
        return AttendanceRecord::where('employee_id', $employeeId)
            ->forMonth($year, $month)
            ->orderBy('attendance_date', 'asc')
            ->get();
    }

    /**
     * Get all attendance records with pagination and filters.
     */
    public function getAllWithPagination(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AttendanceRecord::with(['employee', 'creator', 'approver']);

        // Apply filters
        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('attendance_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('attendance_date', '<=', $filters['date_to']);
        }

        if (isset($filters['has_undertime']) && $filters['has_undertime']) {
            $query->where('undertime_hours', '>', 0);
        }

        if (isset($filters['pending_approval']) && $filters['pending_approval']) {
            $query->whereNull('approved_at');
        }

        return $query->orderBy('attendance_date', 'desc')
                    ->orderBy('employee_id')
                    ->paginate($perPage);
    }

    /**
     * Find existing attendance record for employee and date.
     */
    public function findExisting(int $employeeId, string $date): ?AttendanceRecord
    {
        return AttendanceRecord::where('employee_id', $employeeId)
            ->where('attendance_date', $date)
            ->first();
    }

    /**
     * Get attendance records pending approval.
     */
    public function getPendingApproval(array $filters = []): Collection
    {
        $query = AttendanceRecord::with(['employee', 'creator'])
            ->pendingApproval();

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->orderBy('attendance_date', 'desc')->get();
    }

    /**
     * Get attendance records with undertime.
     */
    public function getWithUndertime(int $employeeId, string $startDate, string $endDate): Collection
    {
        return AttendanceRecord::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('undertime_hours', '>', 0)
            ->orderBy('attendance_date', 'asc')
            ->get();
    }

    /**
     * Bulk create attendance records.
     */
    public function bulkCreate(array $records): int
    {
        DB::beginTransaction();
        try {
            $inserted = 0;

            foreach ($records as $record) {
                // Check for duplicates
                if (!$this->findExisting($record['employee_id'], $record['attendance_date'])) {
                    AttendanceRecord::create($record);
                    $inserted++;
                }
            }

            DB::commit();
            return $inserted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get attendance summary for an employee in a specific month.
     */
    public function getAttendanceSummary(int $employeeId, int $year, int $month): array
    {
        $records = $this->getByMonth($employeeId, $year, $month);

        return [
            'total_days' => $records->count(),
            'present' => $records->where('status', 'Present')->count(),
            'absent' => $records->where('status', 'Absent')->count(),
            'on_leave' => $records->where('status', 'On Leave')->count(),
            'half_day' => $records->where('status', 'Half-Day')->count(),
            'late_count' => $records->where('late_minutes', '>', 0)->count(),
            'total_undertime_hours' => round($records->sum('undertime_hours'), 2),
            'total_overtime_hours' => round($records->sum('overtime_hours'), 2),
            'total_late_minutes' => $records->sum('late_minutes'),
        ];
    }

    /**
     * Get team attendance statistics for a specific date.
     */
    public function getTeamAttendanceStats(array $employeeIds, string $date): array
    {
        $records = AttendanceRecord::with('employee')
            ->whereIn('employee_id', $employeeIds)
            ->where('attendance_date', $date)
            ->get();

        $stats = [
            'total_employees' => count($employeeIds),
            'total_recorded' => $records->count(),
            'present' => $records->where('status', 'Present')->count(),
            'absent' => $records->where('status', 'Absent')->count(),
            'on_leave' => $records->where('status', 'On Leave')->count(),
            'late' => $records->where('late_minutes', '>', 0)->count(),
            'with_undertime' => $records->where('undertime_hours', '>', 0)->count(),
        ];

        $stats['not_recorded'] = $stats['total_employees'] - $stats['total_recorded'];

        return $stats;
    }
}
