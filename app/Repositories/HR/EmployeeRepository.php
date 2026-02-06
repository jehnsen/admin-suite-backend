<?php

namespace App\Repositories\HR;

use App\Interfaces\HR\EmployeeRepositoryInterface;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * Get all employees with optional filtering and pagination.
     */
    public function getAllEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with('user');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['position'])) {
            $query->where('position', 'LIKE', "%{$filters['position']}%");
        }

        if (isset($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('last_name', 'LIKE', "%{$filters['search']}%")
                  ->orWhere('employee_number', 'LIKE', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('last_name')
                    ->orderBy('first_name')
                    ->paginate($perPage);
    }

    /**
     * Get active employees only.
     */
    public function getActiveEmployees(): Collection
    {
        return Employee::active()
                      ->orderBy('last_name')
                      ->orderBy('first_name')
                      ->get();
    }

    /**
     * Find employee by ID.
     */
    public function findEmployeeById(int $id): ?Employee
    {
        return Employee::with([
            'user',
            'leaveRequests',
            'serviceRecords',
            'issuances'
        ])->find($id);
    }

    /**
     * Find employee by employee number.
     */
    public function findEmployeeByEmployeeNumber(string $employeeNumber): ?Employee
    {
        return Employee::where('employee_number', $employeeNumber)->first();
    }

    /**
     * Create a new employee.
     */
    public function createEmployee(array $data): Employee
    {
        return Employee::create($data);
    }

    /**
     * Update employee information.
     */
    public function updateEmployee(int $id, array $data): Employee
    {
        $employee = Employee::findOrFail($id);
        $employee->update($data);
        return $employee->fresh();
    }

    /**
     * Delete employee (soft delete).
     */
    public function deleteEmployee(int $id): bool
    {
        $employee = Employee::findOrFail($id);
        return $employee->delete();
    }

    /**
     * Restore soft-deleted employee.
     */
    public function restoreEmployee(int $id): bool
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        return $employee->restore();
    }

    /**
     * Get employees by position.
     */
    public function getEmployeesByPosition(string $position): Collection
    {
        return Employee::byPosition($position)
                      ->active()
                      ->orderBy('last_name')
                      ->get();
    }

    /**
     * Get employees by status.
     */
    public function getEmployeesByStatus(string $status): Collection
    {
        return Employee::where('status', $status)
                      ->orderBy('last_name')
                      ->get();
    }

    /**
     * Update leave credits for employee.
     */
    public function updateLeaveCredits(int $id, float $vacationCredits, float $sickCredits): Employee
    {
        $employee = Employee::findOrFail($id);
        $employee->update([
            'vacation_leave_credits' => $vacationCredits,
            'sick_leave_credits' => $sickCredits,
        ]);
        return $employee->fresh();
    }

    /**
     * Bulk update monthly leave credits for all active permanent employees.
     * Uses a single query with DB::raw to increment credits.
     *
     * @return int Number of employees updated
     */
    public function bulkUpdateMonthlyLeaveCredits(): int
    {
        return Employee::where('status', 'Active')
            ->where('employment_status', 'Permanent')
            ->update([
                'vacation_leave_credits' => \DB::raw('vacation_leave_credits + 1.25'),
                'sick_leave_credits' => \DB::raw('sick_leave_credits + 1.25'),
            ]);
    }

    /**
     * Search employees by name.
     */
    public function searchEmployeesByName(string $searchTerm): Collection
    {
        return Employee::where('first_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('middle_name', 'LIKE', "%{$searchTerm}%")
                      ->orderBy('last_name')
                      ->get();
    }

    /**
     * Get employee count by status.
     */
    public function getEmployeeCountByStatus(): array
    {
        return Employee::selectRaw('status, COUNT(*) as count')
                      ->groupBy('status')
                      ->pluck('count', 'status')
                      ->toArray();
    }

    /**
     * Get employee count by position.
     */
    public function getEmployeeCountByPosition(): array
    {
        return Employee::selectRaw('position, COUNT(*) as count')
                      ->groupBy('position')
                      ->orderBy('count', 'desc')
                      ->pluck('count', 'position')
                      ->toArray();
    }
}
