<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveRequestRequest;
use App\Http\Resources\HR\LeaveRequestResource;
use App\Services\HR\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group HR Management - Leave Requests
 *
 * APIs for managing employee leave requests with approval workflow.
 */
class LeaveRequestController extends Controller
{
    public function __construct(
        private LeaveRequestService $leaveRequestService
    ) {}

    /**
     * Get all leave requests
     *
     * Retrieve a paginated list of leave requests with optional filtering.
     *
     * @queryParam status string Filter by status. Example: Pending
     * @queryParam leave_type string Filter by leave type. Example: Vacation Leave
     * @queryParam employee_id integer Filter by employee ID. Example: 1
     * @queryParam start_date string Filter by start date. Example: 2024-01-01
     * @queryParam end_date string Filter by end date. Example: 2024-12-31
     *
     * @response 200 {
     *   "data": []
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'leave_type', 'employee_id', 'start_date', 'end_date']);
        $perPage = $request->input('per_page', 15);

        $leaveRequests = $this->leaveRequestService->getAllLeaveRequests($filters, $perPage);

        return LeaveRequestResource::collection($leaveRequests);
    }

    /**
     * Create leave request
     *
     * Create a new leave request with automatic leave credit validation.
     *
     * @bodyParam employee_id integer required Employee ID. Example: 1
     * @bodyParam leave_type string required Type of leave. Example: Vacation Leave
     * @bodyParam start_date string required Start date (Y-m-d). Example: 2024-07-01
     * @bodyParam end_date string required End date (Y-m-d). Example: 2024-07-05
     * @bodyParam reason string Reason for leave. Example: Family vacation
     *
     * @response 201 {
     *   "message": "Leave request created successfully.",
     *   "data": {}
     * }
     * @response 422 {
     *   "message": "Insufficient vacation leave credits. Available: 10.5 days."
     * }
     */
    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        try {
            $leaveRequest = $this->leaveRequestService->createLeaveRequest($request->validated());

            return response()->json([
                'message' => 'Leave request created successfully.',
                'data' => new LeaveRequestResource($leaveRequest),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get leave request details
     *
     * @urlParam id integer required Leave request ID. Example: 1
     *
     * @response 200 {
     *   "data": {}
     * }
     */
    public function show(int $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->findLeaveRequestById($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Leave request not found.'], 404);
        }

        return response()->json([
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    /**
     * Recommend leave request
     *
     * Immediate supervisor recommends approval of leave request.
     *
     * @urlParam id integer required Leave request ID. Example: 1
     * @bodyParam recommended_by integer required Employee ID of recommender. Example: 5
     * @bodyParam remarks string Optional remarks. Example: Approved by immediate supervisor
     *
     * @response 200 {
     *   "message": "Leave request recommended successfully.",
     *   "data": {}
     * }
     */
    public function recommend(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'recommended_by' => 'required|integer|exists:employees,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        $leaveRequest = $this->leaveRequestService->recommendLeaveRequest(
            $id,
            $validated['recommended_by'],
            $validated['remarks'] ?? null
        );

        return response()->json([
            'message' => 'Leave request recommended successfully.',
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    /**
     * Approve leave request
     *
     * Final approval of leave request with automatic leave credit deduction.
     *
     * @urlParam id integer required Leave request ID. Example: 1
     * @bodyParam approved_by integer required Employee ID of approver. Example: 2
     * @bodyParam remarks string Optional remarks. Example: Approved
     *
     * @response 200 {
     *   "message": "Leave request approved successfully.",
     *   "data": {}
     * }
     * @response 422 {
     *   "message": "Insufficient leave credits."
     * }
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'approved_by' => 'required|integer|exists:employees,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $leaveRequest = $this->leaveRequestService->approveLeaveRequest(
                $id,
                $validated['approved_by'],
                $validated['remarks'] ?? null
            );

            return response()->json([
                'message' => 'Leave request approved successfully.',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Disapprove leave request
     *
     * Reject a leave request with reason.
     *
     * @urlParam id integer required Leave request ID. Example: 1
     * @bodyParam disapproved_by integer required Employee ID of disapprover. Example: 2
     * @bodyParam reason string required Reason for disapproval. Example: Insufficient manpower
     *
     * @response 200 {
     *   "message": "Leave request disapproved.",
     *   "data": {}
     * }
     */
    public function disapprove(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'disapproved_by' => 'required|integer|exists:employees,id',
            'reason' => 'required|string|max:500',
        ]);

        $leaveRequest = $this->leaveRequestService->disapproveLeaveRequest(
            $id,
            $validated['disapproved_by'],
            $validated['reason']
        );

        return response()->json([
            'message' => 'Leave request disapproved.',
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    /**
     * Cancel leave request
     *
     * Cancel a leave request with automatic leave credit restoration if already approved.
     *
     * @urlParam id integer required Leave request ID. Example: 1
     *
     * @response 200 {
     *   "message": "Leave request cancelled successfully.",
     *   "data": {}
     * }
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $leaveRequest = $this->leaveRequestService->cancelLeaveRequest($id);

            return response()->json([
                'message' => 'Leave request cancelled successfully.',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get pending leave requests
     *
     * Retrieve all pending leave requests requiring action.
     *
     * @response 200 {
     *   "data": []
     * }
     */
    public function pending(): AnonymousResourceCollection
    {
        $leaveRequests = $this->leaveRequestService->getPendingLeaveRequests();

        return LeaveRequestResource::collection($leaveRequests);
    }

    /**
     * Get leave statistics
     *
     * Get leave statistics for a specific employee.
     *
     * @queryParam employee_id integer required Employee ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "total_leaves": 15,
     *     "approved_leaves": 12,
     *     "pending_leaves": 2,
     *     "current_year_leaves": 8,
     *     "total_days_used": 45.5
     *   }
     * }
     */
    public function statistics(Request $request): JsonResponse
    {
        $employeeId = $request->input('employee_id');

        if (!$employeeId) {
            return response()->json(['message' => 'Employee ID is required.'], 422);
        }

        $statistics = $this->leaveRequestService->getLeaveStatistics($employeeId);

        return response()->json([
            'data' => $statistics,
        ]);
    }
}
