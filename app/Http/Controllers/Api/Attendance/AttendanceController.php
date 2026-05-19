<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CorrectDtrRequest;
use App\Http\Requests\Attendance\ImportAttendanceRequest;
use App\Http\Resources\Attendance\AttendanceImportBatchResource;
use App\Http\Resources\Attendance\AttendanceLogResource;
use App\Http\Resources\Attendance\DailyTimeRecordResource;
use App\Models\Employee;
use App\Services\Attendance\AttendanceImportService;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceImportService $importService,
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Upload and process a biometric attendance CSV.
     * POST /api/attendance/import
     */
    public function import(ImportAttendanceRequest $request): JsonResponse
    {
        try {
            $batch = $this->importService->createBatch(
                $request->file('file'),
                $request->user()->id,
                $request->input('period_start'),
                $request->input('period_end')
            );

            $result = $this->importService->processBatch($batch->id);

            $batch->refresh();

            return response()->json([
                'message'   => 'Attendance import completed.',
                'data'      => new AttendanceImportBatchResource($batch->load('uploader')),
                'processed' => $result['processed'],
                'errors'    => $result['errors'],
            ], 201);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to process attendance file.'], 500);
        }
    }

    /**
     * List all import batches.
     * GET /api/attendance/import-batches
     */
    public function importBatches(Request $request): AnonymousResourceCollection
    {
        $batches = $this->attendanceService->getBatches(
            $request->only(['status', 'period_start', 'period_end']),
            $this->getPerPage($request)
        );

        return AttendanceImportBatchResource::collection($batches);
    }

    /**
     * Show a single import batch with its raw logs.
     * GET /api/attendance/import-batches/{id}
     */
    public function showBatch(string $uuid): JsonResponse
    {
        $id = \App\Models\AttendanceImportBatch::where('uuid', $uuid)->value('id') ?? 0;
        $batch = $this->attendanceService->getBatch($id);

        if (!$batch) {
            return response()->json(['message' => 'Import batch not found.'], 404);
        }

        return response()->json(['data' => new AttendanceImportBatchResource($batch)]);
    }

    /**
     * List raw punch logs for a batch.
     * GET /api/attendance/import-batches/{id}/logs
     */
    public function batchLogs(string $uuid): JsonResponse
    {
        $id = \App\Models\AttendanceImportBatch::where('uuid', $uuid)->value('id') ?? 0;
        $batch = $this->attendanceService->getBatch($id);

        if (!$batch) {
            return response()->json(['message' => 'Import batch not found.'], 404);
        }

        $logs = $batch->logs()->with('employee')->orderBy('employee_id')->orderBy('punched_at')->get();

        return response()->json(['data' => AttendanceLogResource::collection($logs)]);
    }

    /**
     * Get DTR for a specific employee (paginated by day).
     * GET /api/attendance/dtr/{employeeId}
     */
    public function dtrByEmployee(Request $request, string $uuid): AnonymousResourceCollection
    {
        $employeeId = Employee::where('uuid', $uuid)->value('id') ?? 0;

        $records = $this->attendanceService->getDtrForEmployee(
            $employeeId,
            $request->only(['year', 'month', 'date_from', 'date_to']),
            $this->getPerPage($request, 31)
        );

        return DailyTimeRecordResource::collection($records);
    }

    /**
     * Get monthly summary for an employee.
     * GET /api/attendance/summary/{employeeId}?year=&month=
     */
    public function summary(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'year'  => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $employeeId = Employee::where('uuid', $uuid)->value('id') ?? 0;

        $summary = $this->attendanceService->getDtrSummary(
            $employeeId,
            (int) $request->input('year'),
            (int) $request->input('month')
        );

        return response()->json(['data' => $summary]);
    }

    /**
     * List all DTR records (admin-level view with filters).
     * GET /api/attendance/dtr
     */
    public function allDtr(Request $request): AnonymousResourceCollection
    {
        $records = $this->attendanceService->getAllDtr(
            $request->only(['employee_id', 'year', 'month', 'date_from', 'date_to', 'is_absent', 'has_late']),
            $this->getPerPage($request)
        );

        return DailyTimeRecordResource::collection($records);
    }

    /**
     * Correct a DTR entry (manual override).
     * PATCH /api/attendance/dtr/{employeeId}/{date}
     */
    public function correctDtr(CorrectDtrRequest $request, string $uuid, string $date): JsonResponse
    {
        try {
            $employeeId = Employee::where('uuid', $uuid)->value('id') ?? 0;

            if (!$employeeId) {
                return response()->json(['message' => 'Employee not found.'], 404);
            }

            $dtr = $this->attendanceService->correctDtr(
                $employeeId,
                $date,
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'message' => 'DTR entry corrected successfully.',
                'data'    => new DailyTimeRecordResource($dtr),
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Failed to correct DTR entry.'], 500);
        }
    }

    /**
     * Export DTR data for an employee for a given month (JSON for frontend to render/export).
     * GET /api/attendance/export/dtr/{employeeId}?year=&month=
     */
    public function exportDtr(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'year'  => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $employeeId = Employee::where('uuid', $uuid)->value('id') ?? 0;

        if (!$employeeId) {
            return response()->json(['message' => 'Employee not found.'], 404);
        }

        $data = $this->attendanceService->buildDtrExport(
            $employeeId,
            (int) $request->input('year'),
            (int) $request->input('month')
        );

        return response()->json(['data' => $data]);
    }

}
