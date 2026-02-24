<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Procurement\EvaluateQuotationRequest;
use App\Http\Requests\Procurement\StoreQuotationRequest;
use App\Http\Requests\Procurement\UpdateQuotationRequest;

class QuotationController extends Controller
{
    protected $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    /**
     * Get all quotations
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['purchase_request_id', 'supplier_id', 'status', 'is_selected']);
        $perPage = $request->input('per_page', 15);

        $quotations = $this->quotationService->getAllQuotations($filters, $perPage);

        return response()->json($quotations);
    }

    /**
     * Get quotation by ID
     */
    public function show(int $id): JsonResponse
    {
        $quotation = $this->quotationService->getQuotationById($id);

        if (!$quotation) {
            return response()->json(['message' => 'Quotation not found.'], 404);
        }

        return response()->json(['data' => $quotation]);
    }

    /**
     * Get quotations by purchase request
     */
    public function byPurchaseRequest(int $prId): JsonResponse
    {
        $quotations = $this->quotationService->getQuotationsByPurchaseRequest($prId);

        return response()->json(['data' => $quotations]);
    }

    /**
     * Create new quotation
     */
    public function store(StoreQuotationRequest $request): JsonResponse
    {
        try {
            $quotation = $this->quotationService->createQuotation($request->validated());

            return response()->json([
                'message' => 'Quotation created successfully.',
                'data'    => $quotation,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update quotation
     */
    public function update(UpdateQuotationRequest $request, int $id): JsonResponse
    {
        try {
            $quotation = $this->quotationService->updateQuotation($id, $request->validated());

            return response()->json([
                'message' => 'Quotation updated successfully.',
                'data'    => $quotation,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete quotation
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->quotationService->deleteQuotation($id);

            return response()->json(['message' => 'Quotation deleted successfully.']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Select quotation (award)
     */
    public function select(int $id): JsonResponse
    {
        try {
            $quotation = $this->quotationService->selectQuotation($id);

            return response()->json([
                'message' => 'Quotation selected successfully.',
                'data'    => $quotation,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Evaluate quotations for a purchase request
     */
    public function evaluate(EvaluateQuotationRequest $request, int $prId): JsonResponse
    {
        try {
            $this->quotationService->evaluateQuotations($prId, $request->validated()['evaluations']);

            return response()->json(['message' => 'Quotations evaluated successfully.']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
