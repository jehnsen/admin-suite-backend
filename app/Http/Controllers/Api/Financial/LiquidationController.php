<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Financial\LiquidationResource;
use App\Http\Requests\Financial\AddLiquidationItemRequest;
use App\Http\Requests\Financial\StoreLiquidationRequest;
use App\Http\Requests\Financial\UpdateLiquidationRequest;
use App\Services\Financial\LiquidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LiquidationController extends Controller
{
    protected $liquidationService;

    public function __construct(LiquidationService $liquidationService)
    {
        $this->liquidationService = $liquidationService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['cash_advance_id', 'employee_id', 'status', 'date_from', 'date_to']);
        $perPage = $this->getPerPage($request);

        $liquidations = $this->liquidationService->getAllLiquidations($filters, $perPage);

        return LiquidationResource::collection($liquidations);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\Liquidation::where('uuid', $uuid)->value('id') ?? 0;
        $liquidation = $this->liquidationService->getLiquidationById($id);

        if (!$liquidation) {
            return response()->json(['message' => 'Liquidation not found.'], 404);
        }

        return response()->json(['data' => new LiquidationResource($liquidation)]);
    }

    public function store(StoreLiquidationRequest $request): JsonResponse
    {
        try {
            $liquidation = $this->liquidationService->createLiquidation($request->validated());

            return response()->json([
                'message' => 'Liquidation created successfully.',
                'data'    => new LiquidationResource($liquidation),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateLiquidationRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Liquidation::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $liquidation = $this->liquidationService->updateLiquidation($id, $request->validated());

            return response()->json([
                'message' => 'Liquidation updated successfully.',
                'data'    => new LiquidationResource($liquidation),
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
        $id = \App\Models\Liquidation::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->liquidationService->deleteLiquidation($id);

            return response()->json(['message' => 'Liquidation deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function addItem(AddLiquidationItemRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Liquidation::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $item = $this->liquidationService->addLiquidationItem($id, $request->validated());

            return response()->json([
                'message' => 'Liquidation item added successfully.',
                'data'    => $item,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Liquidation::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $liquidation = $this->liquidationService->approveLiquidation($id, $request->user()->id);

            return response()->json([
                'message' => 'Liquidation approved successfully. Cash advance status updated.',
                'data'    => new LiquidationResource($liquidation),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function reject(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Liquidation::where('uuid', $uuid)->value('id') ?? 0;
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $liquidation = $this->liquidationService->rejectLiquidation(
                $id,
                $request->user()->id,
                $validated['reason']
            );

            return response()->json([
                'message' => 'Liquidation rejected.',
                'data'    => new LiquidationResource($liquidation),
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
        $liquidations = $this->liquidationService->getPendingLiquidations($perPage);

        return LiquidationResource::collection($liquidations);
    }

    public function byCashAdvance(int $caId): JsonResponse
    {
        $liquidations = $this->liquidationService->getLiquidationsByCashAdvance($caId);

        return response()->json(['data' => LiquidationResource::collection($liquidations)]);
    }
}
