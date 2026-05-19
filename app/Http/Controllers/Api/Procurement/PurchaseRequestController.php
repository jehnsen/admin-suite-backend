<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Procurement\PurchaseRequestResource;
use App\Services\Procurement\PurchaseRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Procurement\StorePurchaseRequestRequest;
use App\Http\Requests\Procurement\UpdatePurchaseRequestRequest;

class PurchaseRequestController extends Controller
{
    protected $prService;

    public function __construct(PurchaseRequestService $prService)
    {
        $this->prService = $prService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'status', 'fund_source', 'procurement_mode',
            'requested_by', 'date_from', 'date_to', 'search',
        ]);
        $perPage = $this->getPerPage($request);

        $purchaseRequests = $this->prService->getAllPurchaseRequests($filters, $perPage);

        return PurchaseRequestResource::collection($purchaseRequests);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
        $pr = $this->prService->getPurchaseRequestById($id);

        if (!$pr) {
            return response()->json(['message' => 'Purchase request not found.'], 404);
        }

        return response()->json(['data' => new PurchaseRequestResource($pr)]);
    }

    public function store(StorePurchaseRequestRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $data = array_merge($validated, [
                'requested_by' => $request->user()->id,
                'pr_date'      => $validated['pr_date'] ?? now()->format('Y-m-d'),
                'department'   => $validated['department'] ?? 'Administration',
            ]);

            $pr = $this->prService->createPurchaseRequest($data);

            return response()->json([
                'message' => 'Purchase request created successfully.',
                'data'    => new PurchaseRequestResource($pr),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdatePurchaseRequestRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $pr = $this->prService->updatePurchaseRequest($id, $request->validated());

            return response()->json([
                'message' => 'Purchase request updated successfully.',
                'data'    => new PurchaseRequestResource($pr),
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
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->prService->deletePurchaseRequest($id);

            return response()->json(['message' => 'Purchase request deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function submit(string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $pr = $this->prService->submitPurchaseRequest($id);

            return response()->json([
                'message' => 'Purchase request submitted successfully.',
                'data'    => new PurchaseRequestResource($pr),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function recommend(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
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
                'data'    => new PurchaseRequestResource($pr),
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
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
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
                'data'    => new PurchaseRequestResource($pr),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function disapprove(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
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
                'data'    => new PurchaseRequestResource($pr),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function cancel(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PurchaseRequest::where('uuid', $uuid)->value('id') ?? 0;
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $pr = $this->prService->cancelPurchaseRequest($id, $validated['reason']);

            return response()->json([
                'message' => 'Purchase request cancelled.',
                'data'    => new PurchaseRequestResource($pr),
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
        $prs = $this->prService->getPendingPurchaseRequests($perPage);

        return PurchaseRequestResource::collection($prs);
    }

    public function statistics(): JsonResponse
    {
        $statistics = $this->prService->getPurchaseRequestStatistics();

        return response()->json(['data' => $statistics]);
    }
}
