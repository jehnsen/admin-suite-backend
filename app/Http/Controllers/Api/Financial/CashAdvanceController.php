<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Services\Financial\CashAdvanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashAdvanceController extends Controller
{
    protected $cashAdvanceService;

    public function __construct(CashAdvanceService $cashAdvanceService)
    {
        $this->cashAdvanceService = $cashAdvanceService;
    }

    /**
     * Get all cash advances
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['employee_id', 'status', 'purpose', 'date_from', 'date_to']);
            $perPage = $request->input('per_page', 15);

            $cashAdvances = $this->cashAdvanceService->getAllCashAdvances($filters, $perPage);

            return response()->json($cashAdvances);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get cash advance by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $cashAdvance = $this->cashAdvanceService->getCashAdvanceById($id);

            if (!$cashAdvance) {
                return response()->json(['message' => 'Cash advance not found.'], 404);
            }

            return response()->json(['data' => $cashAdvance]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new cash advance
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $cashAdvance = $this->cashAdvanceService->createCashAdvance($request->all());

            return response()->json([
                'message' => 'Cash advance created successfully.',
                'data' => $cashAdvance,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update cash advance
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $cashAdvance = $this->cashAdvanceService->updateCashAdvance($id, $request->all());

            return response()->json([
                'message' => 'Cash advance updated successfully.',
                'data' => $cashAdvance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete cash advance
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->cashAdvanceService->deleteCashAdvance($id);

            return response()->json(['message' => 'Cash advance deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve cash advance
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $approvedBy = $request->input('approved_by');
            $cashAdvance = $this->cashAdvanceService->approveCashAdvance($id, $approvedBy);

            return response()->json([
                'message' => 'Cash advance approved successfully.',
                'data' => $cashAdvance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Release cash advance
     */
    public function release(Request $request, int $id): JsonResponse
    {
        try {
            $releasedBy = $request->input('released_by');
            $cashAdvance = $this->cashAdvanceService->releaseCashAdvance($id, $releasedBy);

            return response()->json([
                'message' => 'Cash advance released successfully.',
                'data' => $cashAdvance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending cash advances
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $cashAdvances = $this->cashAdvanceService->getPendingCashAdvances($perPage);

            return response()->json($cashAdvances);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get overdue cash advances
     */
    public function overdue(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $cashAdvances = $this->cashAdvanceService->getOverdueCashAdvances($perPage);

            return response()->json($cashAdvances);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get cash advances by employee
     */
    public function byEmployee(int $employeeId): JsonResponse
    {
        try {
            $cashAdvances = $this->cashAdvanceService->getCashAdvancesByEmployee($employeeId);

            return response()->json(['data' => $cashAdvances]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get cash advance statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->cashAdvanceService->getCashAdvanceStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
