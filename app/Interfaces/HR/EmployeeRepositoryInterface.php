<?php

namespace App\Interfaces\HR;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{
    /**
     * Get all employees with optional filtering and pagination.
     */
    public function getAllEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get active employees only.
     */
    public function getActiveEmployees(): Collection;

    /**
     * Find employee by ID.
     */
    public function findEmployeeById(int $id): ?Employee;

    /**
     * Find employee by employee number.
     */
    public function findEmployeeByEmployeeNumber(string $employeeNumber): ?Employee;

    /**
     * Create a new employee.
     */
    public function createEmployee(array $data): Employee;

    /**
     * Update employee information.
     */
    public function updateEmployee(int $id, array $data): Employee;

    /**
     * Delete employee (soft delete).
     */
    public function deleteEmployee(int $id): bool;

    /**
     * Restore soft-deleted employee.
     */
    public function restoreEmployee(int $id): bool;

    /**
     * Get employees by position.
     */
    public function getEmployeesByPosition(string $position): Collection;

    /**
     * Get employees by status.
     */
    public function getEmployeesByStatus(string $status): Collection;

    /**
     * Update leave credits for employee.
     */
    public function updateLeaveCredits(int $id, float $vacationCredits, float $sickCredits): Employee;

    /**
     * Bulk update monthly leave credits for all active permanent employees.
     * Increments vacation_leave_credits and sick_leave_credits by 1.25 days.
     *
     * @return int Number of employees updated
     */
    public function bulkUpdateMonthlyLeaveCredits(): int;

    /**
     * Search employees by name.
     */
    public function searchEmployeesByName(string $searchTerm): Collection;

    /**
     * Get employee count by status.
     */
    public function getEmployeeCountByStatus(): array;

    /**
     * Get employee count by position.
     */
    public function getEmployeeCountByPosition(): array;
}
