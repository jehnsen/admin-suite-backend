<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    protected $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Get all deliveries
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'purchase_order_id',
                'supplier_id',
                'date_from',
                'date_to'
            ]);
            $perPage = $request->input('per_page', 15);

            $deliveries = $this->deliveryService->getAllDeliveries($filters, $perPage);

            return response()->json($deliveries);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get delivery by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $delivery = $this->deliveryService->getDeliveryById($id);

            if (!$delivery) {
                return response()->json(['message' => 'Delivery not found.'], 404);
            }

            return response()->json(['data' => $delivery]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get deliveries by purchase order
     */
    public function byPurchaseOrder(Request $request, int $poId): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $deliveries = $this->deliveryService->getDeliveriesByPurchaseOrder($poId, $perPage);

            return response()->json($deliveries);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new delivery
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['received_by'] = $request->user()->id;
            $data['received_at'] = now();

            $delivery = $this->deliveryService->createDelivery($data);

            return response()->json([
                'message' => 'Delivery created successfully.',
                'data' => $delivery,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update delivery
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $delivery = $this->deliveryService->updateDelivery($id, $request->all());

            return response()->json([
                'message' => 'Delivery updated successfully.',
                'data' => $delivery,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete delivery
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deliveryService->deleteDelivery($id);

            return response()->json(['message' => 'Delivery deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Inspect delivery
     */
    public function inspect(Request $request, int $id): JsonResponse
    {
        try {
            $inspectionData = [
                'inspected_by' => $request->user()->id,
                'inspection_result' => $request->input('inspection_result'),
                'inspection_remarks' => $request->input('inspection_remarks'),
            ];

            $delivery = $this->deliveryService->inspectDelivery($id, $inspectionData);

            return response()->json([
                'message' => 'Delivery inspected successfully.',
                'data' => $delivery,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Accept delivery
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        try {
            $delivery = $this->deliveryService->acceptDelivery($id, $request->user()->id);

            return response()->json([
                'message' => 'Delivery accepted successfully.',
                'data' => $delivery,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject delivery
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $delivery = $this->deliveryService->rejectDelivery($id, $request->input('reason'));

            return response()->json([
                'message' => 'Delivery rejected.',
                'data' => $delivery,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get pending deliveries
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $deliveries = $this->deliveryService->getPendingDeliveries($perPage);

            return response()->json($deliveries);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get delivery statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->deliveryService->getDeliveryStatistics();

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
