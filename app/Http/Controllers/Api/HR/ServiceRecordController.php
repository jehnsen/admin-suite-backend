<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreServiceRecordRequest;
use App\Http\Resources\HR\ServiceRecordResource;
use App\Services\HR\ServiceRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group HR Management - Service Records
 *
 * APIs for managing employee service records (201 files).
 */
class ServiceRecordController extends Controller
{
    public function __construct(
        private ServiceRecordService $serviceRecordService
    ) {}

    /**
     * Get service records by employee
     *
     * Retrieve all service records for a specific employee.
     *
     * @urlParam employeeId integer required Employee ID. Example: 1
     *
     * @response 200 {
     *   "data": []
     * }
     */
    public function byEmployee(int $employeeId): AnonymousResourceCollection
    {
        $serviceRecords = $this->serviceRecordService->getServiceHistory($employeeId);

        return ServiceRecordResource::collection($serviceRecords);
    }

    /**
     * Create service record
     *
     * Add a new service record for an employee.
     *
     * @bodyParam employee_id integer required Employee ID. Example: 1
     * @bodyParam date_from string required Start date (Y-m-d). Example: 2024-01-01
     * @bodyParam designation string required Position title. Example: Teacher II
     * @bodyParam salary_grade integer required Salary grade. Example: 12
     * @bodyParam monthly_salary number required Monthly salary. Example: 35000
     *
     * @response 201 {
     *   "message": "Service record created successfully.",
     *   "data": {}
     * }
     */
    public function store(StoreServiceRecordRequest $request): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->createServiceRecord($request->validated());

        return response()->json([
            'message' => 'Service record created successfully.',
            'data' => new ServiceRecordResource($serviceRecord),
        ], 201);
    }

    /**
     * Get service record details
     *
     * @urlParam id integer required Service record ID. Example: 1
     *
     * @response 200 {
     *   "data": {}
     * }
     */
    public function show(int $id): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->findServiceRecordById($id);

        if (!$serviceRecord) {
            return response()->json(['message' => 'Service record not found.'], 404);
        }

        return response()->json([
            'data' => new ServiceRecordResource($serviceRecord),
        ]);
    }

    /**
     * Update service record
     *
     * @urlParam id integer required Service record ID. Example: 1
     *
     * @response 200 {
     *   "message": "Service record updated successfully.",
     *   "data": {}
     * }
     */
    public function update(StoreServiceRecordRequest $request, int $id): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->updateServiceRecord($id, $request->validated());

        return response()->json([
            'message' => 'Service record updated successfully.',
            'data' => new ServiceRecordResource($serviceRecord),
        ]);
    }

    /**
     * Delete service record
     *
     * @urlParam id integer required Service record ID. Example: 1
     *
     * @response 200 {
     *   "message": "Service record deleted successfully."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $this->serviceRecordService->deleteServiceRecord($id);

        return response()->json([
            'message' => 'Service record deleted successfully.',
        ]);
    }
}
