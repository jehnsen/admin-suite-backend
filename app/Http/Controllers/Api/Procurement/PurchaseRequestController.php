<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\PurchaseRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Procurement\StorePurchaseRequestRequest;
use App\Http\Requests\Procurement\UpdatePurchaseRequestRequest;

class PurchaseRequestController extends Controller
{
    protected $prService;

    public function __construct(PurchaseRequestService $prService)
    {
        $this->prService = $prService;
    }

    /**
     * Get all purchase requests
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'fund_source', 'procurement_mode',
            'requested_by', 'date_from', 'date_to', 'search',
        ]);
        $perPage = $request->input('per_page', 15);

        $purchaseRequests = $this->prService->getAllPurchaseRequests($filters, $perPage);

        return response()->json($purchaseRequests);
    }

    /**
     * Get purchase request by ID
     */
    public function show(int $id): JsonResponse
    {
        $pr = $this->prService->getPurchaseRequestById($id);

        if (!$pr) {
            return response()->json(['message' => 'Purchase request not found.'], 404);
        }

        return response()->json(['data' => $pr]);
    }

    /**
     * Create new purchase request
     */
    public function store(StorePurchaseRequestRequest $request): JsonResponse
    {
        try {
            $pr = $this->prService->createPurchaseRequest($request->validated());

            return response()->json([
                'message' => 'Purchase request created successfully.',
                'data'    => $pr,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update purchase request
     */
    public function update(UpdatePurchaseRequestRequest $request, int $id): JsonResponse
    {
        try {
            $pr = $this->prService->updatePurchaseRequest($id, $request->validated());

            return response()->json([
                'message' => 'Purchase request updated successfully.',
                'data'    => $pr,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete purchase request
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->prService->deletePurchaseRequest($id);

            return response()->json(['message' => 'Purchase request deleted successfully.']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Submit purchase request
     */
    public function submit(int $id): JsonResponse
    {
        try {
            $pr = $this->prService->submitPurchaseRequest($id);

            return response()->json([
                'message' => 'Purchase request submitted successfully.',
                'data'    => $pr,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Recommend purchase request
     */
    public function recommend(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $pr = $this->prService->recommendPurchaseRequest(
                $id,
                $request->user()->id,
                $validated['remarks'] ?? null
            );

            return response()->json([
                'message' => 'Purchase request recommended successfully.',
                'data'    => $pr,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve purchase request
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $pr = $this->prService->approvePurchaseRequest(
                $id,
                $request->user()->id,
                $validated['remarks'] ?? null
            );

            return response()->json([
                'message' => 'Purchase request approved successfully.',
                'data'    => $pr,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Disapprove purchase request
     */
    public function disapprove(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $pr = $this->prService->disapprovePurchaseRequest(
                $id,
                $request->user()->id,
                $validated['reason']
            );

            return response()->json([
                'message' => 'Purchase request disapproved.',
                'data'    => $pr,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancel purchase request
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $pr = $this->prService->cancelPurchaseRequest($id, $validated['reason']);

            return response()->json([
                'message' => 'Purchase request cancelled.',
                'data'    => $pr,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get pending purchase requests
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $prs = $this->prService->getPendingPurchaseRequests($perPage);

        return response()->json($prs);
    }

    /**
     * Get purchase request statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->prService->getPurchaseRequestStatistics();

        return response()->json(['data' => $statistics]);
    }
}
