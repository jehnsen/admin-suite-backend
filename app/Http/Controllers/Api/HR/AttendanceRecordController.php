<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\ImportAttendanceCSVRequest;
use App\Http\Requests\HR\StoreAttendanceRecordRequest;
use App\Http\Requests\HR\UpdateAttendanceRecordRequest;
use App\Http\Resources\HR\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\HR\AttendanceRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group HR Management - Attendance Records
 *
 * APIs for managing daily time records (DTR) including creation, CSV import, and approval workflows.
 */
class AttendanceRecordController extends Controller
{
    public function __construct(
        private AttendanceRecordService $attendanceService
    ) {}

    /**
     * Get all attendance records
     *
     * Retrieve a paginated list of attendance records with optional filtering.
     *
     * @queryParam employee_id integer Filter by employee. Example: 1
     * @queryParam date_from date Start date filter. Example: 2025-02-01
     * @queryParam date_to date End date filter. Example: 2025-02-28
     * @queryParam status string Filter by status. Example: Present
     * @queryParam has_undertime boolean Filter records with undertime. Example: true
     * @queryParam per_page integer Items per page. Example: 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $filters = $request->only([
            'employee_id', 'date_from', 'date_to',
            'status', 'has_undertime'
        ]);
        $perPage = $request->input('per_page', 15);

        $records = $this->attendanceService->getAllAttendanceRecords($filters, $perPage);

        return AttendanceRecordResource::collection($records);
    }

    /**
     * Get attendance records for specific employee
     *
     * @urlParam employeeId integer required Employee ID. Example: 1
     * @queryParam date_from date Start date. Example: 2025-02-01
     * @queryParam date_to date End date. Example: 2025-02-28
     * @queryParam status string Filter by status. Example: Present
     */
    public function byEmployee(Request $request, int $employeeId): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $filters = $request->only(['date_from', 'date_to', 'status']);
        $records = $this->attendanceService->getEmployeeAttendance($employeeId, $filters);

        return AttendanceRecordResource::collection($records);
    }

    /**
     * Get attendance summary for employee
     *
     * Returns statistics for a specific employee and month.
     *
     * @urlParam employeeId integer required Employee ID. Example: 1
     * @queryParam year integer Year. Example: 2025
     * @queryParam month integer Month (1-12). Example: 2
     */
    public function summary(Request $request, int $employeeId): JsonResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $summary = $this->attendanceService->getEmployeeAttendanceSummary(
            $employeeId,
            $year,
            $month
        );

        return response()->json(['data' => $summary]);
    }

    /**
     * Get single attendance record
     *
     * @urlParam id integer required Attendance record ID. Example: 1
     */
    public function show(int $id): JsonResponse
    {
        $record = $this->attendanceService->findAttendanceRecordById($id);

        if (!$record) {
            return response()->json(['message' => 'Attendance record not found.'], 404);
        }

        $this->authorize('view', $record);

        return response()->json([
            'data' => new AttendanceRecordResource($record),
        ]);
    }

    /**
     * Create new attendance record
     *
     * @bodyParam employee_id integer required Employee ID. Example: 1
     * @bodyParam attendance_date date required Attendance date. Example: 2025-02-06
     * @bodyParam time_in string Time in (HH:MM:SS). Example: 07:30:00
     * @bodyParam time_out string Time out (HH:MM:SS). Example: 16:30:00
     * @bodyParam lunch_out string Lunch out time. Example: 12:00:00
     * @bodyParam lunch_in string Lunch in time. Example: 13:00:00
     * @bodyParam status string Status. Example: Present
     * @bodyParam remarks string Optional remarks.
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $this->authorize('create', AttendanceRecord::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $record = $this->attendanceService->createAttendanceRecord($data);

            return response()->json([
                'message' => 'Attendance record created successfully.',
                'data' => new AttendanceRecordResource($record),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create attendance record.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update attendance record
     *
     * @urlParam id integer required Attendance record ID. Example: 1
     */
    public function update(UpdateAttendanceRecordRequest $request, int $id): JsonResponse
    {
        $record = $this->attendanceService->findAttendanceRecordById($id);

        if (!$record) {
            return response()->json(['message' => 'Attendance record not found.'], 404);
        }

        $this->authorize('update', $record);

        try {
            $record = $this->attendanceService->updateAttendanceRecord($id, $request->validated());

            return response()->json([
                'message' => 'Attendance record updated successfully.',
                'data' => new AttendanceRecordResource($record),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update attendance record.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete attendance record
     *
     * @urlParam id integer required Attendance record ID. Example: 1
     */
    public function destroy(int $id): JsonResponse
    {
        $record = $this->attendanceService->findAttendanceRecordById($id);

        if (!$record) {
            return response()->json(['message' => 'Attendance record not found.'], 404);
        }

        $this->authorize('delete', $record);

        try {
            $this->attendanceService->deleteAttendanceRecord($id);

            return response()->json([
                'message' => 'Attendance record deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete attendance record.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve attendance record
     *
     * @urlParam id integer required Attendance record ID. Example: 1
     */
    public function approve(int $id): JsonResponse
    {
        $record = $this->attendanceService->findAttendanceRecordById($id);

        if (!$record) {
            return response()->json(['message' => 'Attendance record not found.'], 404);
        }

        $this->authorize('approve', $record);

        try {
            $employeeId = auth()->user()->employee?->id;
            $record = $this->attendanceService->approveAttendance($id, $employeeId);

            return response()->json([
                'message' => 'Attendance record approved successfully.',
                'data' => new AttendanceRecordResource($record),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve attendance record.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import attendance records from CSV
     *
     * Bulk upload attendance records via CSV file.
     *
     * @bodyParam file file required CSV file to import.
     */
    public function importCSV(ImportAttendanceCSVRequest $request): JsonResponse
    {
        $this->authorize('uploadCSV', AttendanceRecord::class);

        try {
            $file = $request->file('file');
            $results = $this->attendanceService->importAttendanceFromCSV(
                $file,
                $request->user()->id
            );

            $statusCode = $results['failed'] > 0 ? 207 : 200; // 207 = Multi-Status

            return response()->json([
                'message' => 'CSV import completed.',
                'data' => $results,
            ], $statusCode);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'CSV import failed.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get attendance statistics
     *
     * Returns aggregated statistics based on filters.
     *
     * @queryParam date_from date Start date. Example: 2025-02-01
     * @queryParam date_to date End date. Example: 2025-02-28
     * @queryParam employee_id integer Filter by employee. Example: 1
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $filters = $request->only(['date_from', 'date_to', 'employee_id']);
        $stats = $this->attendanceService->getAttendanceStatistics($filters);

        return response()->json(['data' => $stats]);
    }
}
