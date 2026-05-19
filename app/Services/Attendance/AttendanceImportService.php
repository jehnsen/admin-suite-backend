<?php

namespace App\Services\Attendance;

use App\Interfaces\Attendance\AttendanceRepositoryInterface;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceImportService
{
    private const GRACE_PERIOD_MINUTES = 10;

    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepository
    ) {}

    /**
     * Store the uploaded file and create an import batch record.
     */
    public function createBatch(UploadedFile $file, int $uploadedBy, string $periodStart, string $periodEnd): \App\Models\AttendanceImportBatch
    {
        $storedName = 'attendance/' . now()->format('Ymd_His') . '_' . uniqid() . '.csv';
        Storage::disk('local')->put($storedName, $file->get());

        return $this->attendanceRepository->createBatch([
            'uploaded_by'        => $uploadedBy,
            'file_name'          => $storedName,
            'original_file_name' => $file->getClientOriginalName(),
            'period_start'       => $periodStart,
            'period_end'         => $periodEnd,
            'status'             => 'pending',
        ]);
    }

    /**
     * Parse and process the CSV file for a given batch.
     * Returns ['processed' => int, 'errors' => int, 'error_message' => string|null].
     */
    public function processBatch(int $batchId): array
    {
        $batch = $this->attendanceRepository->findBatchById($batchId);

        if (!$batch) {
            return ['processed' => 0, 'errors' => 0, 'error_message' => 'Batch not found.'];
        }

        $this->attendanceRepository->updateBatch($batchId, ['status' => 'processing']);

        try {
            $csvContent = Storage::disk('local')->get($batch->file_name);

            if ($csvContent === null) {
                throw new \RuntimeException('CSV file not found in storage.');
            }

            $rows        = $this->parseCsv($csvContent);
            $recordCount = count($rows);

            $this->attendanceRepository->updateBatch($batchId, ['record_count' => $recordCount]);

            [$logs, $errorCount, $errorMessage] = $this->buildLogs($rows, $batchId);

            $processed = 0;
            if (!empty($logs)) {
                $processed = $this->attendanceRepository->bulkCreateLogs($logs);
            }

            $this->computeDtrFromBatch($batchId, $batch->period_start->format('Y-m-d'), $batch->period_end->format('Y-m-d'));

            $this->attendanceRepository->updateBatch($batchId, [
                'processed_count' => $processed,
                'error_count'     => $errorCount,
                'status'          => $errorCount > 0 && $processed === 0 ? 'failed' : 'completed',
                'error_message'   => $errorMessage,
            ]);

            return ['processed' => $processed, 'errors' => $errorCount, 'error_message' => $errorMessage];
        } catch (\Throwable $e) {
            $this->attendanceRepository->updateBatch($batchId, [
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse CSV content into an array of associative rows.
     * Handles both "datetime" and "date + time" column formats.
     */
    private function parseCsv(string $content): array
    {
        $lines = array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", $content))));

        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $rows[] = array_combine($headers, array_map('trim', $values));
            }
        }

        return $rows;
    }

    /**
     * Detect CSV format and normalize each row to [employee_number, punched_at].
     * Returns [$logs, $errorCount, $errorMessage].
     */
    private function buildLogs(array $rows, int $batchId): array
    {
        if (empty($rows)) {
            return [[], 0, null];
        }

        $sampleHeaders = array_keys($rows[0]);
        $format        = $this->detectFormat($sampleHeaders);

        $employeeCache = [];
        $logs          = [];
        $errorCount    = 0;
        $errorMessages = [];

        foreach ($rows as $i => $row) {
            try {
                [$employeeNumber, $punchedAt] = $this->normalizeRow($row, $format);

                if (!$employeeNumber || !$punchedAt) {
                    throw new \InvalidArgumentException('Missing employee number or punch timestamp.');
                }

                if (!isset($employeeCache[$employeeNumber])) {
                    $employeeCache[$employeeNumber] = Employee::where('employee_number', $employeeNumber)->value('id');
                }

                $employeeId = $employeeCache[$employeeNumber];

                if (!$employeeId) {
                    throw new \InvalidArgumentException("Employee not found: {$employeeNumber}");
                }

                $carbon = Carbon::parse($punchedAt);

                $logs[] = [
                    'employee_id'     => $employeeId,
                    'import_batch_id' => $batchId,
                    'log_date'        => $carbon->toDateString(),
                    'punched_at'      => $carbon->toDateTimeString(),
                    'source'          => 'biometric_upload',
                ];
            } catch (\Throwable $e) {
                $errorCount++;
                $errorMessages[] = "Row " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        $errorMessage = !empty($errorMessages) ? implode('; ', array_slice($errorMessages, 0, 10)) : null;

        return [$logs, $errorCount, $errorMessage];
    }

    /**
     * Determine the CSV format from header names.
     * Returns 'datetime', 'date_time', or 'unknown'.
     */
    private function detectFormat(array $headers): string
    {
        $joined = implode(',', $headers);

        // Format: emp_no / employee_no / employee_number + datetime / timestamp
        if (preg_match('/datetime|timestamp|punch_time/', $joined)) {
            return 'datetime';
        }

        // Format: separate date + time columns
        if (preg_match('/\bdate\b/', $joined) && preg_match('/\btime\b/', $joined)) {
            return 'date_time';
        }

        // Fallback: try datetime
        return 'datetime';
    }

    /**
     * Normalize a single CSV row to [employee_number, punched_at_string].
     */
    private function normalizeRow(array $row, string $format): array
    {
        // Resolve employee number from multiple possible column names
        $employeeNumber = $row['employee_number']
            ?? $row['emp_no']
            ?? $row['employee_no']
            ?? $row['id']
            ?? $row['emp_id']
            ?? null;

        if ($format === 'date_time') {
            $date = $row['date'] ?? null;
            $time = $row['time'] ?? null;
            $punchedAt = ($date && $time) ? "{$date} {$time}" : null;
        } else {
            $punchedAt = $row['datetime']
                ?? $row['timestamp']
                ?? $row['punch_time']
                ?? $row['date_time']
                ?? null;
        }

        return [$employeeNumber ? trim($employeeNumber) : null, $punchedAt];
    }

    /**
     * Group raw logs by employee+date, pick earliest as time_in / latest as time_out,
     * then compute DTR fields and upsert into daily_time_records.
     */
    private function computeDtrFromBatch(int $batchId, string $periodStart, string $periodEnd): void
    {
        $logs = $this->attendanceRepository->getLogsByBatch($batchId);

        if ($logs->isEmpty()) {
            return;
        }

        // Build a holiday set for the period
        $holidaySet = Holiday::inRange($periodStart, $periodEnd)
            ->pluck('type', 'holiday_date')
            ->mapKeys(fn($v, $k) => Carbon::parse($k)->toDateString())
            ->all();

        // Cache employee schedule settings
        $employeeIds    = $logs->pluck('employee_id')->unique()->all();
        $employeeShapes = Employee::whereIn('id', $employeeIds)
            ->get(['id', 'standard_time_in', 'standard_time_out'])
            ->keyBy('id');

        // Group: employee_id -> date -> [punched_at...]
        $grouped = [];
        foreach ($logs as $log) {
            $grouped[$log->employee_id][$log->log_date->toDateString()][] = $log->punched_at;
        }

        foreach ($grouped as $employeeId => $dates) {
            $employee = $employeeShapes->get($employeeId);

            foreach ($dates as $dateStr => $punches) {
                usort($punches, fn($a, $b) => $a <=> $b);

                $timeIn  = Carbon::parse($punches[0]);
                $timeOut = count($punches) > 1 ? Carbon::parse(end($punches)) : null;

                $isRestDay  = Carbon::parse($dateStr)->isWeekend();
                $isHoliday  = isset($holidaySet[$dateStr]);
                $hoursWorked = 0.0;
                $lateMinutes = 0;
                $undertimeMinutes = 0;

                if ($timeOut && !$isRestDay && !$isHoliday) {
                    $hoursWorked = round($timeIn->diffInMinutes($timeOut) / 60, 2);
                    [$lateMinutes, $undertimeMinutes] = $this->computeLateAndUndertime(
                        $employee,
                        $timeIn,
                        $timeOut
                    );
                }

                $this->attendanceRepository->upsertDtr($employeeId, $dateStr, [
                    'import_batch_id'  => $batchId,
                    'time_in'          => $timeIn->format('H:i:s'),
                    'time_out'         => $timeOut?->format('H:i:s'),
                    'hours_worked'     => $hoursWorked,
                    'late_minutes'     => $lateMinutes,
                    'undertime_minutes' => $undertimeMinutes,
                    'is_absent'        => false,
                    'is_half_day'      => $hoursWorked > 0 && $hoursWorked < 4,
                    'is_holiday'       => $isHoliday,
                    'is_rest_day'      => $isRestDay,
                ]);
            }
        }
    }

    /**
     * Compute late_minutes and undertime_minutes against the employee's standard schedule.
     * Grace period of 10 minutes applies to lateness.
     */
    private function computeLateAndUndertime(
        ?\App\Models\Employee $employee,
        Carbon $timeIn,
        Carbon $timeOut
    ): array {
        if (!$employee || !$employee->standard_time_in || !$employee->standard_time_out) {
            return [0, 0];
        }

        $date         = $timeIn->toDateString();
        $standardIn   = Carbon::parse("{$date} {$employee->standard_time_in}");
        $standardOut  = Carbon::parse("{$date} {$employee->standard_time_out}");

        $lateMinutes = 0;
        if ($timeIn->gt($standardIn->copy()->addMinutes(self::GRACE_PERIOD_MINUTES))) {
            $lateMinutes = (int) $timeIn->diffInMinutes($standardIn);
        }

        $undertimeMinutes = 0;
        if ($timeOut->lt($standardOut)) {
            $undertimeMinutes = (int) $timeOut->diffInMinutes($standardOut);
        }

        return [$lateMinutes, $undertimeMinutes];
    }
}
