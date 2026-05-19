<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AcknowledgeIssuanceRequest;
use App\Http\Requests\Inventory\ReturnIssuanceRequest;
use App\Http\Requests\Inventory\StoreIssuanceRequest;
use App\Http\Requests\Inventory\TransferIssuanceRequest;
use App\Http\Requests\Inventory\UpdateIssuanceRequest;
use App\Http\Resources\Inventory\IssuanceResource;
use App\Models\Issuance;
use App\Services\Inventory\IssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssuanceController extends Controller
{
    protected $issuanceService;

    public function __construct(IssuanceService $issuanceService)
    {
        $this->issuanceService = $issuanceService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only([
                'status',
                'document_type',
                'employee_id',
                'inventory_item_id',
                'from_date',
                'to_date',
            ]);
            $perPage = $this->getPerPage($request);

            $issuances = $this->issuanceService->getAll($filters, $perPage);

            return IssuanceResource::collection($issuances);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = Issuance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $issuance = $this->issuanceService->getById($id);

            if (!$issuance) {
                return response()->json(['message' => 'Issuance record not found.'], 404);
            }

            return response()->json(['data' => new IssuanceResource($issuance)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function store(StoreIssuanceRequest $request): JsonResponse
    {
        try {
            $issuance = $this->issuanceService->create($request->validated());

            return response()->json([
                'message' => 'Issuance record created successfully.',
                'data'    => new IssuanceResource($issuance),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateIssuanceRequest $request, string $uuid): JsonResponse
    {
        $id = Issuance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $issuance = $this->issuanceService->update($id, $request->validated());

            return response()->json([
                'message' => 'Issuance record updated successfully.',
                'data'    => new IssuanceResource($issuance),
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
        $id = Issuance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $deleted = $this->issuanceService->delete($id);

            if (!$deleted) {
                return response()->json(['message' => 'Issuance record not found.'], 404);
            }

            return response()->json(['message' => 'Issuance record deleted successfully.']);
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

            $results = $this->issuanceService->search($term, $perPage);

            return IssuanceResource::collection($results);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function overdue(Request $request): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);
            $results = $this->issuanceService->getOverdue($perPage);

            return IssuanceResource::collection($results);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function byEmployee(Request $request, int $employeeId): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);
            $results = $this->issuanceService->getByEmployee($employeeId, $perPage);

            return IssuanceResource::collection($results);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function acknowledge(AcknowledgeIssuanceRequest $request, string $uuid): JsonResponse
    {
        $id = Issuance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $issuance = $this->issuanceService->acknowledge($id, $request->validated());

            return response()->json([
                'message' => 'Acknowledgement recorded successfully.',
                'data'    => new IssuanceResource($issuance),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function recordReturn(ReturnIssuanceRequest $request, string $uuid): JsonResponse
    {
        $id = Issuance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $issuance = $this->issuanceService->recordReturn($id, $request->validated());

            return response()->json([
                'message' => 'Return recorded successfully.',
                'data'    => new IssuanceResource($issuance),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function transfer(TransferIssuanceRequest $request, string $uuid): JsonResponse
    {
        $id = Issuance::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $validated = $request->validated();
            $newIssuance = $this->issuanceService->transfer(
                $id,
                $validated['new_employee_id'],
                $validated['remarks'] ?? ''
            );

            return response()->json([
                'message' => 'Item transferred successfully.',
                'data'    => new IssuanceResource($newIssuance),
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
            $stats = $this->issuanceService->getStatistics();

            return response()->json(['data' => $stats]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
