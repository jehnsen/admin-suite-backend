<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\PromoteEmployeeRequest;
use App\Http\Requests\HR\StoreEmployeeRequest;
use App\Http\Requests\HR\UpdateEmployeeRequest;
use App\Http\Resources\HR\EmployeeResource;
use App\Services\HR\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group HR Management - Employees
 *
 * APIs for managing employee records, including creation, updates, promotions, and leave credit management.
 */
class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    /**
     * Get all employees
     *
     * Retrieve a paginated list of all employees with optional filtering.
     *
     * @queryParam status string Filter by status (Active, Inactive, On Leave, Retired, Resigned). Example: Active
     * @queryParam position string Filter by position. Example: Teacher I
     * @queryParam employment_status string Filter by employment status (Permanent, Temporary, etc.). Example: Permanent
     * @queryParam search string Search by name or employee number. Example: Juan
     * @queryParam per_page integer Number of items per page. Example: 15
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "employee_number": "EMP-2024-0001",
     *       "full_name": "Juan dela Cruz",
     *       "position": "Teacher I",
     *       "status": "Active"
     *     }
     *   ],
     *   "links": {},
     *   "meta": {}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Authorization check
        $this->authorize('viewAny', \App\Models\Employee::class);

        $filters = $request->only(['status', 'position', 'employment_status', 'search']);
        $perPage = $request->input('per_page', 15);

        $employees = $this->employeeService->getAllEmployees($filters, $perPage);

        return EmployeeResource::collection($employees);
    }

    /**
     * Create a new employee
     *
     * Create a new employee record with optional initial service record.
     *
     * @bodyParam first_name string required Employee's first name. Example: Juan
     * @bodyParam middle_name string Employee's middle name. Example: Santos
     * @bodyParam last_name string required Employee's last name. Example: dela Cruz
     * @bodyParam email string required Email address. Example: juan.delacruz@deped.gov.ph
     * @bodyParam plantilla_item_no string required Plantilla Item Number. Example: ITEM-2024-001
     * @bodyParam position string required Position title. Example: Teacher I
     * @bodyParam employment_status string required Employment status. Example: Permanent
     * @bodyParam date_hired string required Date hired (Y-m-d format). Example: 2024-01-15
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "employee_number": "EMP-2024-0001",
     *     "full_name": "Juan Santos dela Cruz",
     *     "status": "Active"
     *   }
     * }
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        // Authorization check
        $this->authorize('create', \App\Models\Employee::class);

        $employee = $this->employeeService->createEmployee(
            $request->validated(),
            $request->input('service_record', [])
        );

        return response()->json([
            'message' => 'Employee created successfully.',
            'data' => new EmployeeResource($employee),
        ], 201);
    }

    /**
     * Get employee details
     *
     * Retrieve detailed information for a specific employee.
     *
     * @urlParam id integer required Employee ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "employee_number": "EMP-2024-0001",
     *     "full_name": "Juan dela Cruz",
     *     "position": "Teacher I",
     *     "vacation_leave_credits": 15.00,
     *     "sick_leave_credits": 15.00
     *   }
     * }
     * @response 404 {
     *   "message": "Employee not found."
     * }
     */
    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->findEmployeeById($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        // Authorization check
        $this->authorize('view', $employee);

        return response()->json([
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Update employee
     *
     * Update an existing employee's information.
     *
     * @urlParam id integer required Employee ID. Example: 1
     *
     * @response 200 {
     *   "message": "Employee updated successfully.",
     *   "data": {}
     * }
     */
    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        $employee = $this->employeeService->findEmployeeById($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        // Authorization check
        $this->authorize('update', $employee);

        $employee = $this->employeeService->updateEmployee($id, $request->validated());

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Delete employee
     *
     * Soft delete an employee record.
     *
     * @urlParam id integer required Employee ID. Example: 1
     *
     * @response 200 {
     *   "message": "Employee deleted successfully."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $employee = $this->employeeService->findEmployeeById($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        // Authorization check
        $this->authorize('delete', $employee);

        $this->employeeService->deleteEmployee($id);

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }

    /**
     * Promote employee
     *
     * Promote an employee to a new position with updated salary grade.
     *
     * @urlParam id integer required Employee ID. Example: 1
     * @bodyParam new_position string required New position title. Example: Teacher II
     * @bodyParam new_salary_grade integer required New salary grade. Example: 12
     * @bodyParam new_monthly_salary number required New monthly salary. Example: 35000
     * @bodyParam effective_date string required Effective date (Y-m-d). Example: 2024-07-01
     *
     * @response 200 {
     *   "message": "Employee promoted successfully.",
     *   "data": {}
     * }
     */
    public function promote(PromoteEmployeeRequest $request, int $id): JsonResponse
    {
        $employee = $this->employeeService->findEmployeeById($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        // Authorization check
        $this->authorize('promote', $employee);

        $employee = $this->employeeService->promoteEmployee($id, $request->validated());

        return response()->json([
            'message' => 'Employee promoted successfully.',
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Update monthly leave credits
     *
     * Bulk update leave credits for all active permanent employees.
     * Increments vacation_leave_credits and sick_leave_credits by 1.25 days each.
     * This is typically executed at the end of each month.
     *
     * @response 200 {
     *   "message": "Successfully updated leave credits for 45 permanent employees",
     *   "data": {
     *     "updated_count": 45,
     *     "message": "Successfully updated leave credits for 45 permanent employees"
     *   }
     * }
     */
    public function updateMonthlyLeaveCredits(): JsonResponse
    {
        // Authorization check - only Admin Officer and Super Admin can do this
        $this->authorize('updateMonthlyLeaveCredits', \App\Models\Employee::class);

        $result = $this->employeeService->updateMonthlyLeaveCredits();

        return response()->json([
            'message' => $result['message'],
            'data' => $result,
        ]);
    }

    /**
     * Get employee statistics
     *
     * Retrieve overall employee statistics including counts by status and position.
     *
     * @response 200 {
     *   "data": {
     *     "total_employees": 50,
     *     "active_employees": 45,
     *     "by_status": {
     *       "Active": 45,
     *       "Inactive": 3,
     *       "Retired": 2
     *     },
     *     "by_position": {
     *       "Teacher I": 20,
     *       "Teacher II": 15
     *     }
     *   }
     * }
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->employeeService->getEmployeeStatistics();

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Search employees
     *
     * Search employees by name.
     *
     * @queryParam search string required Search term. Example: Juan
     *
     * @response 200 {
     *   "data": []
     * }
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $searchTerm = $request->input('search', '');
        $employees = $this->employeeService->searchEmployees($searchTerm);

        return EmployeeResource::collection($employees);
    }
}
