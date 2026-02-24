<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financial\MarkPaidDisbursementRequest;
use App\Http\Requests\Financial\StoreDisbursementRequest;
use App\Http\Requests\Financial\UpdateDisbursementRequest;
use App\Services\Financial\DisbursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    protected $disbursementService;

    public function __construct(DisbursementService $disbursementService)
    {
        $this->disbursementService = $disbursementService;
    }

    /**
     * Get all disbursements
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['payee', 'fund_source', 'status', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 15);

        $disbursements = $this->disbursementService->getAllDisbursements($filters, $perPage);

        return response()->json($disbursements);
    }

    /**
     * Get disbursement by ID
     */
    public function show(int $id): JsonResponse
    {
        $disbursement = $this->disbursementService->getDisbursementById($id);

        if (!$disbursement) {
            return response()->json(['message' => 'Disbursement not found.'], 404);
        }

        return response()->json(['data' => $disbursement]);
    }

    /**
     * Create new disbursement voucher
     */
    public function store(StoreDisbursementRequest $request): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->createDisbursement($request->validated());

            return response()->json([
                'message' => 'Disbursement voucher created successfully.',
                'data'    => $disbursement,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update disbursement voucher
     */
    public function update(UpdateDisbursementRequest $request, int $id): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->updateDisbursement($id, $request->validated());

            return response()->json([
                'message' => 'Disbursement voucher updated successfully.',
                'data'    => $disbursement,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete disbursement voucher
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->disbursementService->deleteDisbursement($id);

            return response()->json(['message' => 'Disbursement voucher deleted successfully.']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Certify disbursement voucher
     * The authenticated user is always the certifier.
     */
    public function certify(Request $request, int $id): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->certifyDisbursement($id, $request->user()->id);

            return response()->json([
                'message' => 'Disbursement voucher certified successfully.',
                'data'    => $disbursement,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve disbursement voucher
     * The authenticated user is always the approver.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->approveDisbursement($id, $request->user()->id);

            return response()->json([
                'message' => 'Disbursement voucher approved successfully.',
                'data'    => $disbursement,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Mark disbursement as paid
     * The authenticated user is always recorded as the one who marked it paid.
     */
    public function markPaid(MarkPaidDisbursementRequest $request, int $id): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->markAsPaid(
                $id,
                $request->user()->id,
                $request->input('payment_date')
            );

            return response()->json([
                'message' => 'Disbursement marked as paid successfully.',
                'data'    => $disbursement,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get pending disbursements
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $disbursements = $this->disbursementService->getPendingDisbursements($perPage);

        return response()->json($disbursements);
    }

    /**
     * Get disbursement statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->disbursementService->getDisbursementStatistics();

        return response()->json(['data' => $statistics]);
    }
}
