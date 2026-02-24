<?php

namespace App\Services\HR;

use App\Interfaces\HR\AttendanceRecordRepositoryInterface;
use App\Interfaces\HR\EmployeeRepositoryInterface;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class AttendanceRecordService
{
    public function __construct(
        private AttendanceRecordRepositoryInterface $attendanceRepository,
        private EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Get all attendance records with pagination and filters.
     */
    public function getAllAttendanceRecords(array $filters = [], int $perPage = 15)
    {
        return $this->attendanceRepository->getAllWithPagination($filters, $perPage);
    }

    /**
     * Get attendance records for specific employee.
     */
    public function getEmployeeAttendance(int $employeeId, array $filters = [])
    {
        return $this->attendanceRepository->getByEmployee($employeeId, $filters);
    }

    /**
     * Find attendance record by ID.
     */
    public function findAttendanceRecordById(int $id): ?AttendanceRecord
    {
        return $this->attendanceRepository->findById($id);
    }

    /**
     * Create attendance record with automatic calculations.
     */
    public function createAttendanceRecord(array $data): AttendanceRecord
    {
        return DB::transaction(function () use ($data) {
            // Validate employee exists
            $employee = $this->employeeRepository->findEmployeeById($data['employee_id']);

            if (!$employee) {
                throw new \Exception('Employee not found.');
            }

            // Check for duplicate (employee + date)
            $existing = $this->attendanceRepository->findExisting(
                $data['employee_id'],
                $data['attendance_date']
            );

            if ($existing) {
                throw new \Exception('Attendance record already exists for this date.');
            }

            // Calculate attendance metrics automatically
            $data = $this->calculateAttendanceMetrics($employee, $data);

            return $this->attendanceRepository->create($data);
        });
    }

    /**
     * Calculate attendance metrics (undertime, late, overtime).
     * This is the core business logic for DTR calculations.
     */
    private function calculateAttendanceMetrics(Employee $employee, array $data): array
    {
        // Set defaults
        $data['undertime_hours'] = 0.00;
        $data['late_minutes'] = 0;
        $data['overtime_hours'] = 0.00;

        // Only calculate if we have both time_in and time_out
        if (!isset($data['time_in']) || !isset($data['time_out'])) {
            return $data;
        }

        // Parse times in context of the attendance date
        $date = $data['attendance_date'];
        $timeIn = Carbon::parse("{$date} {$data['time_in']}");
        $timeOut = Carbon::parse("{$date} {$data['time_out']}");

        // Get employee's standard working hours
        $standardTimeIn = Carbon::parse("{$date} " . ($employee->standard_time_in ?: '07:30:00'));
        $standardTimeOut = Carbon::parse("{$date} " . ($employee->standard_time_out ?: '16:30:00'));

        // Calculate late minutes (time_in > standard_time_in)
        if ($timeIn->gt($standardTimeIn)) {
            $data['late_minutes'] = $timeIn->diffInMinutes($standardTimeIn);
        }

        // Calculate undertime hours (time_out < standard_time_out)
        if ($timeOut->lt($standardTimeOut)) {
            $data['undertime_hours'] = round($timeOut->diffInMinutes($standardTimeOut) / 60, 2);
        }

        // Calculate overtime hours (time_out > standard_time_out)
        if ($timeOut->gt($standardTimeOut)) {
            $data['overtime_hours'] = round($timeOut->diffInMinutes($standardTimeOut) / 60, 2);
        }

        // Auto-determine status if not explicitly set
        if (!isset($data['status']) || $data['status'] === 'Present') {
            $data['status'] = 'Present';
        }

        return $data;
    }

    /**
     * Update attendance record with recalculation if times changed.
     */
    public function updateAttendanceRecord(int $id, array $data): AttendanceRecord
    {
        return DB::transaction(function () use ($id, $data) {
            $record = $this->attendanceRepository->findById($id);

            if (!$record) {
                throw new \Exception('Attendance record not found.');
            }

            if (!$record->canBeEdited()) {
                throw new \Exception('Cannot edit approved attendance record.');
            }

            // Recalculate metrics if times changed
            if (isset($data['time_in']) || isset($data['time_out'])) {
                $mergedData = array_merge($record->toArray(), $data);
                $data = $this->calculateAttendanceMetrics($record->employee, $mergedData);
            }

            return $this->attendanceRepository->update($id, $data);
        });
    }

    /**
     * Delete attendance record.
     */
    public function deleteAttendanceRecord(int $id): bool
    {
        $record = $this->attendanceRepository->findById($id);

        if (!$record) {
            throw new \Exception('Attendance record not found.');
        }

        if (!$record->canBeEdited()) {
            throw new \Exception('Cannot delete approved attendance record.');
        }

        return $this->attendanceRepository->delete($id);
    }

    /**
     * Approve attendance record.
     */
    public function approveAttendance(int $id, ?int $approvedBy): AttendanceRecord
    {
        return DB::transaction(function () use ($id, $approvedBy) {
            $record = $this->attendanceRepository->findById($id);

            if (!$record) {
                throw new \Exception('Attendance record not found.');
            }

            if ($record->approved_at) {
                throw new \Exception('Attendance record already approved.');
            }

            return $this->attendanceRepository->update($id, [
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Get attendance summary for employee.
     */
    public function getEmployeeAttendanceSummary(int $employeeId, int $year, int $month): array
    {
        return $this->attendanceRepository->getAttendanceSummary($employeeId, $year, $month);
    }

    /**
     * Import attendance records from CSV file.
     * Returns summary of import results.
     */
    public function importAttendanceFromCSV(UploadedFile $file, int $createdBy): array
    {
        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            // Create CSV reader
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0); // First row is header

            $records = [];
            foreach ($csv as $index => $row) {
                $results['total']++;

                try {
                    // Validate row
                    $validated = $this->validateCSVRow($row, $index + 2);

                    // Find employee by employee_number
                    $employee = $this->employeeRepository
                        ->findEmployeeByEmployeeNumber($validated['employee_number']);

                    if (!$employee) {
                        throw new \Exception("Employee not found: {$validated['employee_number']}");
                    }

                    // Check for duplicate
                    $existing = $this->attendanceRepository->findExisting(
                        $employee->id,
                        $validated['attendance_date']
                    );

                    if ($existing) {
                        throw new \Exception("Duplicate record for this date");
                    }

                    // Prepare record
                    $record = [
                        'employee_id' => $employee->id,
                        'attendance_date' => $validated['attendance_date'],
                        'time_in' => $validated['time_in'] ?? null,
                        'time_out' => $validated['time_out'] ?? null,
                        'lunch_out' => $validated['lunch_out'] ?? null,
                        'lunch_in' => $validated['lunch_in'] ?? null,
                        'status' => $validated['status'] ?? 'Present',
                        'remarks' => $validated['remarks'] ?? null,
                        'import_source' => 'CSV Upload',
                        'created_by' => $createdBy,
                    ];

                    // Calculate metrics
                    $record = $this->calculateAttendanceMetrics($employee, $record);

                    $records[] = $record;
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            // Bulk insert
            if (!empty($records)) {
                foreach ($records as $record) {
                    $this->attendanceRepository->create($record);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("CSV import failed: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Validate CSV row data.
     */
    private function validateCSVRow(array $row, int $lineNumber): array
    {
        // Expected CSV format:
        // employee_number, attendance_date, time_in, time_out, lunch_out, lunch_in, status, remarks

        $required = ['employee_number', 'attendance_date'];

        foreach ($required as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // Validate date format
        try {
            Carbon::parse($row['attendance_date']);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: {$row['attendance_date']}");
        }

        // Validate time formats if provided
        $timeFields = ['time_in', 'time_out', 'lunch_out', 'lunch_in'];
        foreach ($timeFields as $field) {
            if (!empty($row[$field])) {
                try {
                    Carbon::parse($row[$field]);
                } catch (\Exception $e) {
                    throw new \Exception("Invalid time format for {$field}: {$row[$field]}");
                }
            }
        }

        // Validate status if provided
        $validStatuses = ['Present', 'Absent', 'On Leave', 'Half-Day', 'Holiday', 'Weekend'];
        if (!empty($row['status']) && !in_array($row['status'], $validStatuses)) {
            throw new \Exception("Invalid status: {$row['status']}");
        }

        return $row;
    }

    /**
     * Get attendance statistics.
     */
    public function getAttendanceStatistics(array $filters = []): array
    {
        // This can be expanded based on specific requirements
        $records = $this->attendanceRepository->getAllWithPagination($filters, PHP_INT_MAX);

        return [
            'total_records' => $records->total(),
            'present' => $records->where('status', 'Present')->count(),
            'absent' => $records->where('status', 'Absent')->count(),
            'on_leave' => $records->where('status', 'On Leave')->count(),
            'total_undertime_hours' => $records->sum('undertime_hours'),
            'total_overtime_hours' => $records->sum('overtime_hours'),
            'late_count' => $records->where('late_minutes', '>', 0)->count(),
        ];
    }
}
