<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Services\Financial\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    protected $budgetService;

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    /**
     * Get all budgets with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'fund_source',
                'classification',
                'fiscal_year',
                'quarter',
                'search'
            ]);
            $perPage = $request->input('per_page', 15);

            $budgets = $this->budgetService->getAllBudgets($filters, $perPage);

            return response()->json($budgets);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get budget by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $budget = $this->budgetService->getBudgetById($id);

            if (!$budget) {
                return response()->json(['message' => 'Budget not found.'], 404);
            }

            return response()->json(['data' => $budget]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new budget
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $budget = $this->budgetService->createBudget($request->all());

            return response()->json([
                'message' => 'Budget created successfully.',
                'data' => $budget,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update budget
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $budget = $this->budgetService->updateBudget($id, $request->all());

            return response()->json([
                'message' => 'Budget updated successfully.',
                'data' => $budget,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete budget
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->budgetService->deleteBudget($id);

            return response()->json(['message' => 'Budget deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve budget
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $budget = $this->budgetService->approveBudget($id, $request->user()->id);

            return response()->json([
                'message' => 'Budget approved successfully.',
                'data' => $budget,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Activate budget
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $budget = $this->budgetService->activateBudget($id);

            return response()->json([
                'message' => 'Budget activated successfully.',
                'data' => $budget,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Close budget
     */
    public function close(int $id): JsonResponse
    {
        try {
            $budget = $this->budgetService->closeBudget($id);

            return response()->json([
                'message' => 'Budget closed successfully.',
                'data' => $budget,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get active budgets
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $budgets = $this->budgetService->getActiveBudgets($perPage);

            return response()->json($budgets);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get budgets by fiscal year
     */
    public function byFiscalYear(Request $request, int $year): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $budgets = $this->budgetService->getBudgetsByFiscalYear($year, $perPage);

            return response()->json($budgets);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get budgets by fund source
     */
    public function byFundSource(Request $request, string $fundSource): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $budgets = $this->budgetService->getBudgetsByFundSource($fundSource, $perPage);

            return response()->json($budgets);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get budget utilization breakdown by fund source
     */
    public function utilization(): JsonResponse
    {
        try {
            $utilization = $this->budgetService->getBudgetUtilization();

            return response()->json(['data' => $utilization]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get nearly depleted budgets (>= 90% utilized)
     */
    public function nearlyDepleted(): JsonResponse
    {
        try {
            $budgets = $this->budgetService->getNearlyDepletedBudgets();

            return response()->json(['data' => $budgets]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get budget statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->budgetService->getBudgetStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Manually update budget utilization from expenses
     */
    public function updateUtilization(int $id): JsonResponse
    {
        try {
            $budget = $this->budgetService->updateBudgetUtilization($id);

            return response()->json([
                'message' => 'Budget utilization updated successfully.',
                'data' => $budget,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
