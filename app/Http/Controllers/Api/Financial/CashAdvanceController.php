<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financial\StoreCashAdvanceRequest;
use App\Http\Requests\Financial\UpdateCashAdvanceRequest;
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
        $filters = $request->only(['employee_id', 'status', 'purpose', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 15);

        $cashAdvances = $this->cashAdvanceService->getAllCashAdvances($filters, $perPage);

        return response()->json($cashAdvances);
    }

    /**
     * Get cash advance by ID
     */
    public function show(int $id): JsonResponse
    {
        $cashAdvance = $this->cashAdvanceService->getCashAdvanceById($id);

        if (!$cashAdvance) {
            return response()->json(['message' => 'Cash advance not found.'], 404);
        }

        return response()->json(['data' => $cashAdvance]);
    }

    /**
     * Create new cash advance
     */
    public function store(StoreCashAdvanceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;

            $cashAdvance = $this->cashAdvanceService->createCashAdvance($data);

            return response()->json([
                'message' => 'Cash advance created successfully.',
                'data'    => $cashAdvance,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update cash advance
     */
    public function update(UpdateCashAdvanceRequest $request, int $id): JsonResponse
    {
        try {
            $cashAdvance = $this->cashAdvanceService->updateCashAdvance($id, $request->validated());

            return response()->json([
                'message' => 'Cash advance updated successfully.',
                'data'    => $cashAdvance,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
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
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve cash advance
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $cashAdvance = $this->cashAdvanceService->approveCashAdvance($id, $request->user()->id);

            return response()->json([
                'message' => 'Cash advance approved successfully.',
                'data'    => $cashAdvance,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Release cash advance
     */
    public function release(Request $request, int $id): JsonResponse
    {
        try {
            $cashAdvance = $this->cashAdvanceService->releaseCashAdvance($id, $request->user()->id);

            return response()->json([
                'message' => 'Cash advance released successfully.',
                'data'    => $cashAdvance,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get pending cash advances
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $cashAdvances = $this->cashAdvanceService->getPendingCashAdvances($perPage);

        return response()->json($cashAdvances);
    }

    /**
     * Get overdue cash advances
     */
    public function overdue(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $cashAdvances = $this->cashAdvanceService->getOverdueCashAdvances($perPage);

        return response()->json($cashAdvances);
    }

    /**
     * Get cash advances by employee
     */
    public function byEmployee(int $employeeId): JsonResponse
    {
        $cashAdvances = $this->cashAdvanceService->getCashAdvancesByEmployee($employeeId);

        return response()->json(['data' => $cashAdvances]);
    }

    /**
     * Get cash advance statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->cashAdvanceService->getCashAdvanceStatistics();

        return response()->json(['data' => $statistics]);
    }
}
