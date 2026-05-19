<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\BudgetResource;
use App\Services\Financial\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Financial\StoreBudgetRequest;
use App\Http\Requests\Financial\UpdateBudgetRequest;

class BudgetController extends Controller
{
    protected $budgetService;

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only([
                'status', 'fund_source', 'classification',
                'fiscal_year', 'quarter', 'search',
            ]);
            $perPage = $this->getPerPage($request);

            $budgets = $this->budgetService->getAllBudgets($filters, $perPage);

            return BudgetResource::collection($budgets);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\Budget::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $budget = $this->budgetService->getBudgetById($id);

            if (!$budget) {
                return response()->json(['message' => 'Budget not found.'], 404);
            }

            return response()->json(['data' => new BudgetResource($budget)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        try {
            $budget = $this->budgetService->createBudget($request->validated());

            return response()->json([
                'message' => 'Budget created successfully.',
                'data'    => new BudgetResource($budget),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateBudgetRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Budget::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $budget = $this->budgetService->updateBudget($id, $request->validated());

            return response()->json([
                'message' => 'Budget updated successfully.',
                'data'    => new BudgetResource($budget),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $id = \App\Models\Budget::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->budgetService->deleteBudget($id);

            return response()->json(['message' => 'Budget deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Budget::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $budget = $this->budgetService->approveBudget($id, $request->user()->employee?->id);

            return response()->json([
                'message' => 'Budget approved successfully.',
                'data'    => new BudgetResource($budget),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function activate(string $uuid): JsonResponse
    {
        $id = \App\Models\Budget::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $budget = $this->budgetService->activateBudget($id);

            return response()->json([
                'message' => 'Budget activated successfully.',
                'data'    => new BudgetResource($budget),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function close(string $uuid): JsonResponse
    {
        $id = \App\Models\Budget::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $budget = $this->budgetService->closeBudget($id);

            return response()->json([
                'message' => 'Budget closed successfully.',
                'data'    => new BudgetResource($budget),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function active(Request $request): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);
            $budgets = $this->budgetService->getActiveBudgets($perPage);

            return BudgetResource::collection($budgets);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function byFiscalYear(Request $request, int $year): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);
            $budgets = $this->budgetService->getBudgetsByFiscalYear($year, $perPage);

            return BudgetResource::collection($budgets);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function byFundSource(Request $request, string $fundSource): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);
            $budgets = $this->budgetService->getBudgetsByFundSource($fundSource, $perPage);

            return BudgetResource::collection($budgets);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function utilization(): JsonResponse
    {
        try {
            $utilization = $this->budgetService->getBudgetUtilization();

            return response()->json(['data' => $utilization]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function nearlyDepleted(): JsonResponse
    {
        try {
            $budgets = $this->budgetService->getNearlyDepletedBudgets();

            return response()->json(['data' => BudgetResource::collection($budgets)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->budgetService->getBudgetStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function updateUtilization(string $uuid): JsonResponse
    {
        $id = \App\Models\Budget::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $budget = $this->budgetService->updateBudgetUtilization($id);

            return response()->json([
                'message' => 'Budget utilization updated successfully.',
                'data'    => new BudgetResource($budget),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
