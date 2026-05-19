<?php

namespace App\Services\Attendance;

use App\Interfaces\Attendance\AttendanceRepositoryInterface;
use App\Models\DailyTimeRecord;
use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepository
    ) {}

    public function getBatches(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->attendanceRepository->getAllBatches($filters, $perPage);
    }

    public function getBatch(int $id): ?\App\Models\AttendanceImportBatch
    {
        return $this->attendanceRepository->findBatchById($id);
    }

    public function getDtrForEmployee(int $employeeId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->attendanceRepository->getDtrByEmployee($employeeId, $filters, $perPage);
    }

    public function getDtrSummary(int $employeeId, int $year, int $month): array
    {
        return $this->attendanceRepository->getDtrSummary($employeeId, $year, $month);
    }

    public function getAllDtr(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->attendanceRepository->getAllDtr($filters, $perPage);
    }

    /**
     * Apply a manual correction to a single DTR entry.
     */
    public function correctDtr(int $employeeId, string $logDate, array $data, int $correctedBy): DailyTimeRecord
    {
        $dtr = $this->attendanceRepository->findDtr($employeeId, $logDate);

        if (!$dtr) {
            // Create a placeholder if no biometric log exists yet
            $dtr = $this->attendanceRepository->upsertDtr($employeeId, $logDate, [
                'is_absent'             => true,
                'is_manually_corrected' => true,
                'correction_reason'     => $data['correction_reason'] ?? null,
                'corrected_by'          => $correctedBy,
                'corrected_at'          => now(),
            ]);
        }

        $dtr->update(array_merge($data, [
            'is_manually_corrected' => true,
            'corrected_by'          => $correctedBy,
            'corrected_at'          => now(),
        ]));

        return $dtr->fresh(['employee', 'corrector']);
    }

    /**
     * Build an export-ready DTR array for a given employee and month.
     * Suitable for CSV/Excel export or Division office submission.
     */
    public function buildDtrExport(int $employeeId, int $year, int $month): array
    {
        $employee = Employee::with([])->find($employeeId);

        if (!$employee) {
            return [];
        }

        $records = DailyTimeRecord::where('employee_id', $employeeId)
            ->forMonth($year, $month)
            ->orderBy('log_date')
            ->get();

        $summary = $this->attendanceRepository->getDtrSummary($employeeId, $year, $month);

        $rows = $records->map(fn($r) => [
            'date'              => $r->log_date->format('Y-m-d'),
            'day'               => $r->log_date->format('D'),
            'time_in'           => $r->time_in,
            'time_out'          => $r->time_out,
            'hours_worked'      => $r->hours_worked,
            'late_minutes'      => $r->late_minutes,
            'undertime_minutes' => $r->undertime_minutes,
            'remarks'           => $this->buildRemarks($r),
        ])->values()->all();

        return [
            'employee' => [
                'id'              => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'full_name'       => $employee->full_name,
                'position'        => $employee->position,
                'position_title'  => $employee->position_title,
            ],
            'period' => [
                'year'  => $year,
                'month' => $month,
                'label' => \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y'),
            ],
            'rows'    => $rows,
            'summary' => $summary,
        ];
    }

    private function buildRemarks(DailyTimeRecord $r): string
    {
        if ($r->is_rest_day) {
            return 'Rest Day';
        }
        if ($r->is_holiday) {
            return 'Holiday';
        }
        if ($r->is_absent) {
            return 'Absent';
        }
        if ($r->is_half_day) {
            return 'Half Day';
        }
        if ($r->is_manually_corrected) {
            return 'Corrected';
        }
        return '';
    }
}
