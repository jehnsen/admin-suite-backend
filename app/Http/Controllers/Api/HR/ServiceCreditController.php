<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\ApplyServiceCreditOffsetRequest;
use App\Http\Requests\HR\StoreServiceCreditRequest;
use App\Http\Requests\HR\UpdateServiceCreditRequest;
use App\Http\Resources\HR\ServiceCreditResource;
use App\Models\ServiceCredit;
use App\Services\HR\ServiceCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceCreditController extends Controller
{
    public function __construct(
        private ServiceCreditService $serviceCreditService
    ) {}

    /**
     * Get all service credits with pagination and filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ServiceCredit::class);

        $filters = $request->only([
            'employee_id',
            'credit_type',
            'status',
            'work_date_from',
            'work_date_to',
            'expiring_soon'
        ]);

        $perPage = $request->input('per_page', 15);

        $serviceCredits = $this->serviceCreditService->getAllServiceCredits($filters, $perPage);

        return ServiceCreditResource::collection($serviceCredits);
    }

    /**
     * Get pending service credits for approval.
     */
    public function pending(): AnonymousResourceCollection
    {
        $this->authorize('approve', ServiceCredit::class);

        $pendingCredits = $this->serviceCreditService->getPendingServiceCredits();

        return ServiceCreditResource::collection($pendingCredits);
    }

    /**
     * Get service credits for specific employee.
     */
    public function byEmployee(int $employeeId, Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ServiceCredit::class);

        $filters = $request->only(['credit_type', 'status', 'work_date_from', 'work_date_to']);

        $serviceCredits = $this->serviceCreditService->getEmployeeServiceCredits($employeeId, $filters);

        return ServiceCreditResource::collection($serviceCredits);
    }

    /**
     * Get service credit summary for employee.
     */
    public function summary(int $employeeId): JsonResponse
    {
        $this->authorize('viewAny', ServiceCredit::class);

        try {
            $summary = $this->serviceCreditService->getEmployeeServiceCreditSummary($employeeId);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get specific service credit.
     */
    public function show(int $id): ServiceCreditResource
    {
        $serviceCredit = $this->serviceCreditService->findServiceCreditById($id);

        if (!$serviceCredit) {
            abort(404, 'Service credit not found.');
        }

        $this->authorize('view', $serviceCredit);

        return new ServiceCreditResource($serviceCredit);
    }

    /**
     * Create new service credit.
     */
    public function store(StoreServiceCreditRequest $request): JsonResponse
    {
        try {
            $serviceCredit = $this->serviceCreditService->createServiceCredit($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Service credit created successfully.',
                'data' => new ServiceCreditResource($serviceCredit)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update service credit (only pending ones).
     */
    public function update(int $id, UpdateServiceCreditRequest $request): JsonResponse
    {
        $serviceCredit = $this->serviceCreditService->findServiceCreditById($id);

        if (!$serviceCredit) {
            return response()->json([
                'success' => false,
                'message' => 'Service credit not found.'
            ], 404);
        }

        $this->authorize('update', $serviceCredit);

        try {
            $updated = $this->serviceCreditService->updateServiceCredit($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Service credit updated successfully.',
                'data' => new ServiceCreditResource($updated)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete service credit (only if not used).
     */
    public function destroy(int $id): JsonResponse
    {
        $serviceCredit = $this->serviceCreditService->findServiceCreditById($id);

        if (!$serviceCredit) {
            return response()->json([
                'success' => false,
                'message' => 'Service credit not found.'
            ], 404);
        }

        $this->authorize('delete', $serviceCredit);

        try {
            $this->serviceCreditService->deleteServiceCredit($id);

            return response()->json([
                'success' => true,
                'message' => 'Service credit deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Approve service credit.
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        $serviceCredit = $this->serviceCreditService->findServiceCreditById($id);

        if (!$serviceCredit) {
            return response()->json([
                'success' => false,
                'message' => 'Service credit not found.'
            ], 404);
        }

        $this->authorize('approve', $serviceCredit);

        $request->validate([
            'remarks' => ['nullable', 'string', 'max:500']
        ]);

        try {
            $approved = $this->serviceCreditService->approveServiceCredit(
                $id,
                $request->user()->id,
                $request->input('remarks')
            );

            return response()->json([
                'success' => true,
                'message' => 'Service credit approved successfully.',
                'data' => new ServiceCreditResource($approved)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reject service credit.
     */
    public function reject(int $id, Request $request): JsonResponse
    {
        $serviceCredit = $this->serviceCreditService->findServiceCreditById($id);

        if (!$serviceCredit) {
            return response()->json([
                'success' => false,
                'message' => 'Service credit not found.'
            ], 404);
        }

        $this->authorize('approve', $serviceCredit);

        $request->validate([
            'reason' => ['required', 'string', 'max:500']
        ]);

        try {
            $rejected = $this->serviceCreditService->rejectServiceCredit(
                $id,
                $request->user()->id,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Service credit rejected successfully.',
                'data' => new ServiceCreditResource($rejected)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Apply service credit to offset absence (FIFO).
     */
    public function applyOffset(ApplyServiceCreditOffsetRequest $request): JsonResponse
    {
        try {
            $result = $this->serviceCreditService->applyServiceCreditOffset(
                $request->input('employee_id'),
                $request->input('attendance_record_id'),
                $request->input('credits_to_use'),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Service credit offset applied successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Revert service credit offset.
     */
    public function revertOffset(int $offsetId, Request $request): JsonResponse
    {
        $this->authorize('apply_service_credit_offset');

        $request->validate([
            'reason' => ['required', 'string', 'max:500']
        ]);

        try {
            $reverted = $this->serviceCreditService->revertServiceCreditOffset(
                $offsetId,
                $request->user()->id,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Service credit offset reverted successfully.',
                'data' => $reverted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
