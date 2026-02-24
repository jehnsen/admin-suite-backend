<?php

namespace App\Services\HR;

use App\Interfaces\HR\EmployeeRepositoryInterface;
use App\Interfaces\HR\ServiceRecordRepositoryInterface;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeService
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private ServiceRecordRepositoryInterface $serviceRecordRepository
    ) {}

    /**
     * Get all employees with filtering and pagination.
     */
    public function getAllEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->employeeRepository->getAllEmployees($filters, $perPage);
    }

    /**
     * Get active employees.
     */
    public function getActiveEmployees(): Collection
    {
        return $this->employeeRepository->getActiveEmployees();
    }

    /**
     * Find employee by ID.
     */
    public function findEmployeeById(int $id): ?Employee
    {
        return $this->employeeRepository->findEmployeeById($id);
    }

    /**
     * Create a new employee with initial service record.
     * Business Logic: When hiring a new employee, also create their first service record.
     */
    public function createEmployee(array $employeeData, array $serviceRecordData = []): Employee
    {
        return DB::transaction(function () use ($employeeData, $serviceRecordData) {
            // Generate employee number if not provided
            if (!isset($employeeData['employee_number'])) {
                $employeeData['employee_number'] = $this->generateEmployeeNumber();
            }

            // Initialize leave credits (for permanent employees, they get 1.25 days/month)
            if ($employeeData['employment_status'] === 'Permanent') {
                $employeeData['vacation_leave_credits'] = 15.00; // 1 year initial
                $employeeData['sick_leave_credits'] = 15.00;
            }

            // Create employee
            $employee = $this->employeeRepository->createEmployee($employeeData);

            // Create initial service record if data provided
            if (!empty($serviceRecordData)) {
                $serviceRecordData['employee_id'] = $employee->id;
                $serviceRecordData['date_from'] = $employee->date_hired;
                $serviceRecordData['action_type'] = 'New Appointment';

                $this->serviceRecordRepository->createServiceRecord($serviceRecordData);
            }

            return $employee;
        });
    }

    /**
     * Update employee information.
     */
    public function updateEmployee(int $id, array $data): Employee
    {
        return $this->employeeRepository->updateEmployee($id, $data);
    }

    /**
     * Promote employee to a new position.
     * Business Logic: Close current service record and create new one for promotion.
     */
    public function promoteEmployee(int $employeeId, array $promotionData): Employee
    {
        return DB::transaction(function () use ($employeeId, $promotionData) {
            $employee = $this->employeeRepository->findEmployeeById($employeeId);

            if (!$employee) {
                throw new \Exception('Employee not found.');
            }

            // Close current service record
            $this->serviceRecordRepository->closeCurrentServiceRecord(
                $employeeId,
                $promotionData['effective_date']
            );

            // Update employee master data
            $this->employeeRepository->updateEmployee($employeeId, [
                'position' => $promotionData['new_position'],
                'salary_grade' => $promotionData['new_salary_grade'],
                'step_increment' => $promotionData['new_step_increment'] ?? 1,
                'monthly_salary' => $promotionData['new_monthly_salary'],
            ]);

            // Create new service record for promotion
            $this->serviceRecordRepository->createServiceRecord([
                'employee_id' => $employeeId,
                'date_from' => $promotionData['effective_date'],
                'date_to' => null,
                'designation' => $promotionData['new_position'],
                'status_of_appointment' => $employee->employment_status,
                'salary_grade' => $promotionData['new_salary_grade'],
                'step_increment' => $promotionData['new_step_increment'] ?? 1,
                'monthly_salary' => $promotionData['new_monthly_salary'],
                'station_place_of_assignment' => $promotionData['station'] ?? $employee->address,
                'office_entity' => $promotionData['office_entity'] ?? 'DepEd',
                'government_service' => 'Yes',
                'action_type' => 'Promotion',
                'appointment_authority' => $promotionData['appointment_authority'] ?? null,
                'appointment_date' => $promotionData['appointment_date'] ?? null,
                'remarks' => $promotionData['remarks'] ?? null,
            ]);

            return $employee->fresh();
        });
    }

    /**
     * Calculate and update leave credits for all active employees.
     * Business Logic: Permanent employees earn 1.25 days per month for both VL and SL.
     * Optimized: Uses bulk update to execute a single database query.
     */
    public function updateMonthlyLeaveCredits(): array
    {
        $updatedCount = $this->employeeRepository->bulkUpdateMonthlyLeaveCredits();

        return [
            'updated_count' => $updatedCount,
            'message' => "Successfully updated leave credits for {$updatedCount} permanent employees",
        ];
    }

    /**
     * Deduct leave credits when leave is approved.
     */
    public function deductLeaveCredits(int $employeeId, string $leaveType, float $days): Employee
    {
        $employee = $this->employeeRepository->findEmployeeById($employeeId);

        if (!$employee) {
            throw new \Exception('Employee not found.');
        }

        if ($leaveType === 'Vacation Leave') {
            if ($employee->vacation_leave_credits < $days) {
                throw new \Exception('Insufficient vacation leave credits.');
            }
            $newVacationCredits = $employee->vacation_leave_credits - $days;
            $newSickCredits = $employee->sick_leave_credits;
        } elseif ($leaveType === 'Sick Leave') {
            if ($employee->sick_leave_credits < $days) {
                throw new \Exception('Insufficient sick leave credits.');
            }
            $newVacationCredits = $employee->vacation_leave_credits;
            $newSickCredits = $employee->sick_leave_credits - $days;
        } else {
            // For special leaves, no deduction
            return $employee;
        }

        return $this->employeeRepository->updateLeaveCredits(
            $employeeId,
            $newVacationCredits,
            $newSickCredits
        );
    }

    /**
     * Restore leave credits when leave is cancelled.
     */
    public function restoreLeaveCredits(int $employeeId, string $leaveType, float $days): Employee
    {
        $employee = $this->employeeRepository->findEmployeeById($employeeId);

        if (!$employee) {
            throw new \Exception('Employee not found.');
        }

        $newVacationCredits = $employee->vacation_leave_credits;
        $newSickCredits = $employee->sick_leave_credits;

        if ($leaveType === 'Vacation Leave') {
            $newVacationCredits += $days;
        } elseif ($leaveType === 'Sick Leave') {
            $newSickCredits += $days;
        }

        return $this->employeeRepository->updateLeaveCredits(
            $employeeId,
            $newVacationCredits,
            $newSickCredits
        );
    }

    /**
     * Separate employee (resignation, retirement, etc.).
     */
    public function separateEmployee(int $employeeId, string $separationDate, string $reason): Employee
    {
        return DB::transaction(function () use ($employeeId, $separationDate, $reason) {
            // Close current service record
            $this->serviceRecordRepository->closeCurrentServiceRecord($employeeId, $separationDate);

            // Update employee status
            return $this->employeeRepository->updateEmployee($employeeId, [
                'status' => 'Resigned', // or 'Retired' based on reason
                'date_separated' => $separationDate,
            ]);
        });
    }

    /**
     * Generate unique employee number.
     */
    private function generateEmployeeNumber(): string
    {
        $year = Carbon::now()->year;
        $lastEmployee = Employee::withTrashed()
                               ->whereYear('created_at', $year)
                               ->orderBy('employee_number', 'desc')
                               ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "EMP-{$year}-{$newNumber}";
    }

    /**
     * Get employee statistics.
     */
    public function getEmployeeStatistics(): array
    {
        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'Active')->count(),
            'by_status' => $this->employeeRepository->getEmployeeCountByStatus(),
            'by_position' => $this->employeeRepository->getEmployeeCountByPosition(),
        ];
    }

    /**
     * Search employees.
     */
    public function searchEmployees(string $searchTerm): Collection
    {
        return $this->employeeRepository->searchEmployeesByName($searchTerm);
    }

    /**
     * Delete employee.
     */
    public function deleteEmployee(int $id): bool
    {
        return $this->employeeRepository->deleteEmployee($id);
    }
}
