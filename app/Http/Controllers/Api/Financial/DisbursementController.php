<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\DisbursementResource;
use App\Http\Requests\Financial\MarkPaidDisbursementRequest;
use App\Http\Requests\Financial\StoreDisbursementRequest;
use App\Http\Requests\Financial\UpdateDisbursementRequest;
use App\Services\Financial\DisbursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DisbursementController extends Controller
{
    protected $disbursementService;

    public function __construct(DisbursementService $disbursementService)
    {
        $this->disbursementService = $disbursementService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['payee', 'fund_source', 'status', 'date_from', 'date_to']);
        $perPage = $this->getPerPage($request);

        $disbursements = $this->disbursementService->getAllDisbursements($filters, $perPage);

        return DisbursementResource::collection($disbursements);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\Disbursement::where('uuid', $uuid)->value('id') ?? 0;
        $disbursement = $this->disbursementService->getDisbursementById($id);

        if (!$disbursement) {
            return response()->json(['message' => 'Disbursement not found.'], 404);
        }

        return response()->json(['data' => new DisbursementResource($disbursement)]);
    }

    public function store(StoreDisbursementRequest $request): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->createDisbursement($request->validated());

            return response()->json([
                'message' => 'Disbursement voucher created successfully.',
                'data'    => new DisbursementResource($disbursement),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateDisbursementRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Disbursement::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $disbursement = $this->disbursementService->updateDisbursement($id, $request->validated());

            return response()->json([
                'message' => 'Disbursement voucher updated successfully.',
                'data'    => new DisbursementResource($disbursement),
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
        $id = \App\Models\Disbursement::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->disbursementService->deleteDisbursement($id);

            return response()->json(['message' => 'Disbursement voucher deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function certify(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Disbursement::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $disbursement = $this->disbursementService->certifyDisbursement($id, $request->user()->id);

            return response()->json([
                'message' => 'Disbursement voucher certified successfully.',
                'data'    => new DisbursementResource($disbursement),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Disbursement::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $disbursement = $this->disbursementService->approveDisbursement($id, $request->user()->id);

            return response()->json([
                'message' => 'Disbursement voucher approved successfully.',
                'data'    => new DisbursementResource($disbursement),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function markPaid(MarkPaidDisbursementRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Disbursement::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $disbursement = $this->disbursementService->markAsPaid(
                $id,
                $request->user()->id
            );

            return response()->json([
                'message' => 'Disbursement marked as paid successfully.',
                'data'    => new DisbursementResource($disbursement),
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
        $disbursements = $this->disbursementService->getPendingDisbursements($perPage);

        return DisbursementResource::collection($disbursements);
    }

    public function statistics(): JsonResponse
    {
        $statistics = $this->disbursementService->getDisbursementStatistics();

        return response()->json(['data' => $statistics]);
    }
}
