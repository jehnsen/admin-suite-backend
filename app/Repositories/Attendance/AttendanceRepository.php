<?php

namespace App\Repositories\Attendance;

use App\Interfaces\Attendance\AttendanceRepositoryInterface;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceLog;
use App\Models\DailyTimeRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    // --- Import Batches ---

    public function createBatch(array $data): AttendanceImportBatch
    {
        return AttendanceImportBatch::create($data);
    }

    public function updateBatch(int $id, array $data): AttendanceImportBatch
    {
        $batch = AttendanceImportBatch::findOrFail($id);
        $batch->update($data);
        return $batch->fresh(['uploader']);
    }

    public function findBatchById(int $id): ?AttendanceImportBatch
    {
        return AttendanceImportBatch::with(['uploader'])->find($id);
    }

    public function getAllBatches(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AttendanceImportBatch::with(['uploader']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['uploaded_by'])) {
            $query->where('uploaded_by', $filters['uploaded_by']);
        }

        if (!empty($filters['period_start'])) {
            $query->where('period_start', '>=', $filters['period_start']);
        }

        if (!empty($filters['period_end'])) {
            $query->where('period_end', '<=', $filters['period_end']);
        }

        return $query->latest()->paginate($perPage);
    }

    // --- Raw Logs ---

    public function createLog(array $data): AttendanceLog
    {
        return AttendanceLog::create($data);
    }

    public function bulkCreateLogs(array $logs): int
    {
        if (empty($logs)) {
            return 0;
        }

        $now = now();
        foreach ($logs as &$log) {
            $log['created_at'] = $now;
            $log['updated_at'] = $now;
            $log['uuid']       = (string) \Illuminate\Support\Str::uuid();
        }
        unset($log);

        AttendanceLog::insert($logs);
        return count($logs);
    }

    public function getLogsByBatch(int $batchId): Collection
    {
        return AttendanceLog::where('import_batch_id', $batchId)
            ->with(['employee'])
            ->orderBy('employee_id')
            ->orderBy('punched_at')
            ->get();
    }

    public function getLogsByEmployee(int $employeeId, string $start, string $end): Collection
    {
        return AttendanceLog::where('employee_id', $employeeId)
            ->whereBetween('log_date', [$start, $end])
            ->orderBy('punched_at')
            ->get();
    }

    // --- Daily Time Records ---

    public function upsertDtr(int $employeeId, string $logDate, array $data): DailyTimeRecord
    {
        $dtr = DailyTimeRecord::firstOrNew([
            'employee_id' => $employeeId,
            'log_date'    => $logDate,
        ]);

        // Don't overwrite manually corrected records from a bulk import
        if ($dtr->exists && $dtr->is_manually_corrected && empty($data['is_manually_corrected'])) {
            return $dtr;
        }

        $dtr->fill($data);
        $dtr->save();

        return $dtr;
    }

    public function findDtr(int $employeeId, string $logDate): ?DailyTimeRecord
    {
        return DailyTimeRecord::where('employee_id', $employeeId)
            ->where('log_date', $logDate)
            ->with(['employee', 'importBatch', 'corrector'])
            ->first();
    }

    public function getDtrByEmployee(int $employeeId, array $filters = [], int $perPage = 31): LengthAwarePaginator
    {
        $query = DailyTimeRecord::where('employee_id', $employeeId)
            ->with(['importBatch', 'corrector']);

        if (!empty($filters['year']) && !empty($filters['month'])) {
            $query->forMonth((int) $filters['year'], (int) $filters['month']);
        } elseif (!empty($filters['date_from'])) {
            $query->where('log_date', '>=', $filters['date_from']);
            if (!empty($filters['date_to'])) {
                $query->where('log_date', '<=', $filters['date_to']);
            }
        }

        return $query->orderBy('log_date')->paginate($perPage);
    }

    public function getDtrSummary(int $employeeId, int $year, int $month): array
    {
        $records = DailyTimeRecord::where('employee_id', $employeeId)
            ->forMonth($year, $month)
            ->get();

        $workingDays = $records->filter(fn($r) => !$r->is_rest_day && !$r->is_holiday);

        return [
            'total_days'         => $records->count(),
            'working_days'       => $workingDays->count(),
            'days_present'       => $workingDays->where('is_absent', false)->where('is_half_day', false)->count(),
            'days_absent'        => $workingDays->where('is_absent', true)->count(),
            'half_days'          => $workingDays->where('is_half_day', true)->count(),
            'holidays'           => $records->where('is_holiday', true)->count(),
            'rest_days'          => $records->where('is_rest_day', true)->count(),
            'late_count'         => $workingDays->where('late_minutes', '>', 0)->count(),
            'total_late_minutes' => (int) $workingDays->sum('late_minutes'),
            'total_undertime_minutes' => (int) $workingDays->sum('undertime_minutes'),
            'total_hours_worked' => round((float) $workingDays->sum('hours_worked'), 2),
        ];
    }

    public function getDtrByBatch(int $batchId): Collection
    {
        return DailyTimeRecord::where('import_batch_id', $batchId)
            ->with(['employee'])
            ->orderBy('employee_id')
            ->orderBy('log_date')
            ->get();
    }

    public function getAllDtr(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DailyTimeRecord::with(['employee', 'importBatch']);

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['year']) && !empty($filters['month'])) {
            $query->forMonth((int) $filters['year'], (int) $filters['month']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('log_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('log_date', '<=', $filters['date_to']);
        }

        if (isset($filters['is_absent'])) {
            $query->where('is_absent', (bool) $filters['is_absent']);
        }

        if (isset($filters['has_late']) && $filters['has_late']) {
            $query->where('late_minutes', '>', 0);
        }

        return $query->orderBy('log_date')->orderBy('employee_id')->paginate($perPage);
    }
}
