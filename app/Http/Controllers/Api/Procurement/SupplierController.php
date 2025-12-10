<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    /**
     * Get all suppliers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'business_type', 'classification', 'search']);
            $perPage = $request->input('per_page', 15);

            $suppliers = $this->supplierService->getAllSuppliers($filters, $perPage);

            return response()->json($suppliers);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get supplier by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $supplier = $this->supplierService->getSupplierById($id);

            if (!$supplier) {
                return response()->json(['message' => 'Supplier not found.'], 404);
            }

            return response()->json(['data' => $supplier]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new supplier
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $supplier = $this->supplierService->createSupplier($request->all());

            return response()->json([
                'message' => 'Supplier created successfully.',
                'data' => $supplier,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update supplier
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $supplier = $this->supplierService->updateSupplier($id, $request->all());

            return response()->json([
                'message' => 'Supplier updated successfully.',
                'data' => $supplier,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete supplier
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->supplierService->deleteSupplier($id);

            return response()->json(['message' => 'Supplier deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get active suppliers
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $suppliers = $this->supplierService->getActiveSuppliers($perPage);

            return response()->json($suppliers);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search suppliers
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword', '');
            $perPage = $request->input('per_page', 15);

            $suppliers = $this->supplierService->searchSuppliers($keyword, $perPage);

            return response()->json($suppliers);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get supplier statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->supplierService->getSupplierStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
