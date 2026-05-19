<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Procurement\SupplierResource;
use App\Services\Procurement\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Procurement\StoreSupplierRequest;
use App\Http\Requests\Procurement\UpdateSupplierRequest;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $filters = $request->only(['status', 'business_type', 'classification', 'search']);
            $perPage = $this->getPerPage($request);

            $suppliers = $this->supplierService->getAllSuppliers($filters, $perPage);

            return SupplierResource::collection($suppliers);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\Supplier::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $supplier = $this->supplierService->getSupplierById($id);

            if (!$supplier) {
                return response()->json(['message' => 'Supplier not found.'], 404);
            }

            return response()->json(['data' => new SupplierResource($supplier)]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        try {
            $supplier = $this->supplierService->createSupplier($request->validated());

            return response()->json([
                'message' => 'Supplier created successfully.',
                'data'    => new SupplierResource($supplier),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateSupplierRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Supplier::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $supplier = $this->supplierService->updateSupplier($id, $request->validated());

            return response()->json([
                'message' => 'Supplier updated successfully.',
                'data'    => new SupplierResource($supplier),
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
        $id = \App\Models\Supplier::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->supplierService->deleteSupplier($id);

            return response()->json(['message' => 'Supplier deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function active(Request $request): AnonymousResourceCollection
    {
        try {
            $perPage = $this->getPerPage($request);
            $suppliers = $this->supplierService->getActiveSuppliers($perPage);

            return SupplierResource::collection($suppliers);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        try {
            $keyword = $request->input('keyword', '');
            $perPage = $this->getPerPage($request);

            $suppliers = $this->supplierService->searchSuppliers($keyword, $perPage);

            return SupplierResource::collection($suppliers);
        } catch (\Exception $e) {
            report($e);
            abort(500, 'An unexpected error occurred. Please try again.');
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->supplierService->getSupplierStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }
}
