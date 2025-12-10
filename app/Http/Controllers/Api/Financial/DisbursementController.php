<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
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
        try {
            $filters = $request->only(['payee', 'fund_source', 'status', 'date_from', 'date_to']);
            $perPage = $request->input('per_page', 15);

            $disbursements = $this->disbursementService->getAllDisbursements($filters, $perPage);

            return response()->json($disbursements);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get disbursement by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->getDisbursementById($id);

            if (!$disbursement) {
                return response()->json(['message' => 'Disbursement not found.'], 404);
            }

            return response()->json(['data' => $disbursement]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new disbursement voucher
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->createDisbursement($request->all());

            return response()->json([
                'message' => 'Disbursement voucher created successfully.',
                'data' => $disbursement,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update disbursement voucher
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $disbursement = $this->disbursementService->updateDisbursement($id, $request->all());

            return response()->json([
                'message' => 'Disbursement voucher updated successfully.',
                'data' => $disbursement,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Certify disbursement voucher
     */
    public function certify(Request $request, int $id): JsonResponse
    {
        try {
            $certifiedBy = $request->input('certified_by');
            $disbursement = $this->disbursementService->certifyDisbursement($id, $certifiedBy);

            return response()->json([
                'message' => 'Disbursement voucher certified successfully.',
                'data' => $disbursement,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve disbursement voucher
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $approvedBy = $request->input('approved_by');
            $disbursement = $this->disbursementService->approveDisbursement($id, $approvedBy);

            return response()->json([
                'message' => 'Disbursement voucher approved successfully.',
                'data' => $disbursement,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark disbursement as paid
     */
    public function markPaid(Request $request, int $id): JsonResponse
    {
        try {
            $paidBy = $request->input('paid_by');
            $paymentDate = $request->input('payment_date');

            $disbursement = $this->disbursementService->markAsPaid($id, $paidBy, $paymentDate);

            return response()->json([
                'message' => 'Disbursement marked as paid successfully.',
                'data' => $disbursement,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending disbursements
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $disbursements = $this->disbursementService->getPendingDisbursements($perPage);

            return response()->json($disbursements);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get disbursement statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->disbursementService->getDisbursementStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
