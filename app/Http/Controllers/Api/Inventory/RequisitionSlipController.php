<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ApproveRequisitionSlipRequest;
use App\Http\Requests\Inventory\ReleaseRequisitionSlipRequest;
use App\Http\Requests\Inventory\StoreRequisitionSlipRequest;
use App\Http\Requests\Inventory\UpdateRequisitionSlipRequest;
use App\Http\Resources\Inventory\RequisitionSlipResource;
use App\Models\RequisitionSlip;
use App\Services\Inventory\RequisitionSlipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequisitionSlipController extends Controller
{
    protected $risService;

    public function __construct(RequisitionSlipService $risService)
    {
        $this->risService = $risService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only(['status', 'employee_id', 'from_date', 'to_date']);
            $perPage = $this->getPerPage($request);

            $slips = $this->risService->getAll($filters, $perPage);

            return RequisitionSlipResource::collection($slips);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = RequisitionSlip::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $slip = $this->risService->getById($id);

            if (!$slip) {
                return response()->json(['message' => 'Requisition slip not found.'], 404);
            }

            return response()->json(['data' => new RequisitionSlipResource($slip)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function store(StoreRequisitionSlipRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $items = $validated['items'];
            unset($validated['items']);

            $slip = $this->risService->create($validated, $items);

            return response()->json([
                'message' => 'Requisition slip created successfully.',
                'data'    => new RequisitionSlipResource($slip),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateRequisitionSlipRequest $request, string $uuid): JsonResponse
    {
        $id = RequisitionSlip::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $validated = $request->validated();
            $items = $validated['items'] ?? null;
            unset($validated['items']);

            $slip = $this->risService->update($id, $validated, $items);

            return response()->json([
                'message' => 'Requisition slip updated successfully.',
                'data'    => new RequisitionSlipResource($slip),
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
        $id = RequisitionSlip::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $deleted = $this->risService->delete($id);

            if (!$deleted) {
                return response()->json(['message' => 'Requisition slip not found.'], 404);
            }

            return response()->json(['message' => 'Requisition slip deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        try {
            $term = $request->input('q', '');
            $perPage = $this->getPerPage($request);

            if (empty($term)) {
                abort(400, 'Search term is required.');
            }

            return RequisitionSlipResource::collection($this->risService->search($term, $perPage));
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function pending(Request $request): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);

            return RequisitionSlipResource::collection($this->risService->getPending($perPage));
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function approve(ApproveRequisitionSlipRequest $request, string $uuid): JsonResponse
    {
        $id = RequisitionSlip::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $validated = $request->validated();
            $slip = $this->risService->approve(
                $id,
                $validated['approved_by_employee_id'],
                $validated['approved_quantities']
            );

            return response()->json([
                'message' => 'Requisition slip approved successfully.',
                'data'    => new RequisitionSlipResource($slip),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function release(ReleaseRequisitionSlipRequest $request, string $uuid): JsonResponse
    {
        $id = RequisitionSlip::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $validated = $request->validated();
            $slip = $this->risService->release(
                $id,
                $validated['released_by_employee_id'],
                $validated['issued_quantities']
            );

            return response()->json([
                'message' => 'Requisition slip released successfully.',
                'data'    => new RequisitionSlipResource($slip),
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
        $id = RequisitionSlip::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $slip = $this->risService->cancel($id, $request->input('remarks', ''));

            return response()->json([
                'message' => 'Requisition slip cancelled.',
                'data'    => new RequisitionSlipResource($slip),
            ]);
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
            return response()->json(['data' => $this->risService->getStatistics()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
