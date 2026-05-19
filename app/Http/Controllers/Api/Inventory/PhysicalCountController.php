<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\PhysicalCountResource;
use App\Services\Inventory\PhysicalCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Inventory\StorePhysicalCountRequest;
use App\Http\Requests\Inventory\UpdatePhysicalCountRequest;

class PhysicalCountController extends Controller
{
    protected $physicalCountService;

    public function __construct(PhysicalCountService $physicalCountService)
    {
        $this->physicalCountService = $physicalCountService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only(['inventory_item_id', 'variance_type', 'date_from', 'date_to']);
            $perPage = $this->getPerPage($request);

            $physicalCounts = $this->physicalCountService->getAllPhysicalCounts($filters, $perPage);

            return PhysicalCountResource::collection($physicalCounts);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\PhysicalCount::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $physicalCount = $this->physicalCountService->getPhysicalCountById($id);

            if (!$physicalCount) {
                return response()->json(['message' => 'Physical count record not found.'], 404);
            }

            return response()->json(['data' => new PhysicalCountResource($physicalCount)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function store(StorePhysicalCountRequest $request): JsonResponse
    {
        try {
            $physicalCount = $this->physicalCountService->createPhysicalCount($request->validated());

            return response()->json([
                'message' => 'Physical count recorded successfully.',
                'data'    => new PhysicalCountResource($physicalCount),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdatePhysicalCountRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\PhysicalCount::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $physicalCount = $this->physicalCountService->updatePhysicalCount($id, $request->validated());

            return response()->json([
                'message' => 'Physical count updated successfully.',
                'data'    => new PhysicalCountResource($physicalCount),
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
        $id = \App\Models\PhysicalCount::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->physicalCountService->deletePhysicalCount($id);

            return response()->json(['message' => 'Physical count deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function withVariances(Request $request): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);
            $physicalCounts = $this->physicalCountService->getItemsWithVariances($perPage);

            return PhysicalCountResource::collection($physicalCounts);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }
}
