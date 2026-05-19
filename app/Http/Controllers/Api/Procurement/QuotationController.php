<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Procurement\QuotationResource;
use App\Services\Procurement\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['purchase_request_id', 'supplier_id', 'status', 'is_selected']);
        $perPage = $this->getPerPage($request);

        $quotations = $this->quotationService->getAllQuotations($filters, $perPage);

        return QuotationResource::collection($quotations);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\Quotation::where('uuid', $uuid)->value('id') ?? 0;
        $quotation = $this->quotationService->getQuotationById($id);

        if (!$quotation) {
            return response()->json(['message' => 'Quotation not found.'], 404);
        }

        return response()->json(['data' => new QuotationResource($quotation)]);
    }

    public function byPurchaseRequest(int $prId): JsonResponse
    {
        $quotations = $this->quotationService->getQuotationsByPurchaseRequest($prId);

        return response()->json(['data' => QuotationResource::collection($quotations)]);
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        try {
            $quotation = $this->quotationService->createQuotation($request->validated());

            return response()->json([
                'message' => 'Quotation created successfully.',
                'data'    => new QuotationResource($quotation),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateQuotationRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Quotation::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $quotation = $this->quotationService->updateQuotation($id, $request->validated());

            return response()->json([
                'message' => 'Quotation updated successfully.',
                'data'    => new QuotationResource($quotation),
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
        $id = \App\Models\Quotation::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->quotationService->deleteQuotation($id);

            return response()->json(['message' => 'Quotation deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function select(string $uuid): JsonResponse
    {
        $id = \App\Models\Quotation::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $quotation = $this->quotationService->selectQuotation($id);

            return response()->json([
                'message' => 'Quotation selected successfully.',
                'data'    => new QuotationResource($quotation),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function evaluate(EvaluateQuotationRequest $request, int $prId): JsonResponse
    {
        try {
            $this->quotationService->evaluateQuotations($prId, $request->validated()['evaluations']);

            return response()->json(['message' => 'Quotations evaluated successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
