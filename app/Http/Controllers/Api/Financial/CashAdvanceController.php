<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\CashAdvanceResource;
use App\Http\Requests\Financial\StoreCashAdvanceRequest;
use App\Http\Requests\Financial\UpdateCashAdvanceRequest;
use App\Services\Financial\CashAdvanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashAdvanceController extends Controller
{
    protected $cashAdvanceService;

    public function __construct(CashAdvanceService $cashAdvanceService)
    {
        $this->cashAdvanceService = $cashAdvanceService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['employee_id', 'status', 'purpose', 'date_from', 'date_to']);
        $perPage = $this->getPerPage($request);

        $cashAdvances = $this->cashAdvanceService->getAllCashAdvances($filters, $perPage);

        return CashAdvanceResource::collection($cashAdvances);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\CashAdvance::where('uuid', $uuid)->value('id') ?? 0;
        $cashAdvance = $this->cashAdvanceService->getCashAdvanceById($id);

        if (!$cashAdvance) {
            return response()->json(['message' => 'Cash advance not found.'], 404);
        }

        return response()->json(['data' => new CashAdvanceResource($cashAdvance)]);
    }

    public function store(StoreCashAdvanceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;

            $cashAdvance = $this->cashAdvanceService->createCashAdvance($data);

            return response()->json([
                'message' => 'Cash advance created successfully.',
                'data'    => new CashAdvanceResource($cashAdvance),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateCashAdvanceRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\CashAdvance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $cashAdvance = $this->cashAdvanceService->updateCashAdvance($id, $request->validated());

            return response()->json([
                'message' => 'Cash advance updated successfully.',
                'data'    => new CashAdvanceResource($cashAdvance),
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
        $id = \App\Models\CashAdvance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->cashAdvanceService->deleteCashAdvance($id);

            return response()->json(['message' => 'Cash advance deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\CashAdvance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $cashAdvance = $this->cashAdvanceService->approveCashAdvance($id, $request->user()->id);

            return response()->json([
                'message' => 'Cash advance approved successfully.',
                'data'    => new CashAdvanceResource($cashAdvance),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function release(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\CashAdvance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $cashAdvance = $this->cashAdvanceService->releaseCashAdvance($id, $request->user()->id);

            return response()->json([
                'message' => 'Cash advance released successfully.',
                'data'    => new CashAdvanceResource($cashAdvance),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function pending(Request $request): AnonymousResourceCollection
    {
        $perPage = $this->getPerPage($request);
        $cashAdvances = $this->cashAdvanceService->getPendingCashAdvances($perPage);

        return CashAdvanceResource::collection($cashAdvances);
    }

    public function overdue(Request $request): AnonymousResourceCollection
    {
        $perPage = $this->getPerPage($request);
        $cashAdvances = $this->cashAdvanceService->getOverdueCashAdvances($perPage);

        return CashAdvanceResource::collection($cashAdvances);
    }

    public function byEmployee(Request $request, string $employeeId): AnonymousResourceCollection
    {
        $id = \App\Models\Employee::where('uuid', $employeeId)->value('id') ?? 0;
        $cashAdvances = $this->cashAdvanceService->getByEmployee($id, $this->getPerPage($request));

        return CashAdvanceResource::collection($cashAdvances);
    }

    public function statistics(): JsonResponse
    {
        $statistics = $this->cashAdvanceService->getCashAdvanceStatistics();

        return response()->json(['data' => $statistics]);
    }
}
