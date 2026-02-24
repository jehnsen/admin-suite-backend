<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Procurement\InspectDeliveryRequest;
use App\Http\Requests\Procurement\StoreDeliveryRequest;
use App\Http\Requests\Procurement\UpdateDeliveryRequest;

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
        $filters = $request->only(['status', 'purchase_order_id', 'supplier_id', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 15);

        $deliveries = $this->deliveryService->getAllDeliveries($filters, $perPage);

        return response()->json($deliveries);
    }

    /**
     * Get delivery by ID
     */
    public function show(int $id): JsonResponse
    {
        $delivery = $this->deliveryService->getDeliveryById($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found.'], 404);
        }

        return response()->json(['data' => $delivery]);
    }

    /**
     * Get deliveries by purchase order
     */
    public function byPurchaseOrder(Request $request, int $poId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $deliveries = $this->deliveryService->getDeliveriesByPurchaseOrder($poId, $perPage);

        return response()->json($deliveries);
    }

    /**
     * Create new delivery
     */
    public function store(StoreDeliveryRequest $request): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'received_by' => $request->user()->id,
                'received_at' => now(),
            ]);

            $delivery = $this->deliveryService->createDelivery($data);

            return response()->json([
                'message' => 'Delivery created successfully.',
                'data'    => $delivery,
            ], 201);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update delivery
     */
    public function update(UpdateDeliveryRequest $request, int $id): JsonResponse
    {
        try {
            $delivery = $this->deliveryService->updateDelivery($id, $request->validated());

            return response()->json([
                'message' => 'Delivery updated successfully.',
                'data'    => $delivery,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
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
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Inspect delivery
     * The authenticated user is always recorded as the inspector.
     */
    public function inspect(InspectDeliveryRequest $request, int $id): JsonResponse
    {
        try {
            $inspectionData = [
                'inspected_by'       => $request->user()->id,
                'inspection_result'  => $request->input('inspection_result'),
                'inspection_remarks' => $request->input('inspection_remarks'),
            ];

            $delivery = $this->deliveryService->inspectDelivery($id, $inspectionData);

            return response()->json([
                'message' => 'Delivery inspected successfully.',
                'data'    => $delivery,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Accept delivery
     * The authenticated user is always recorded as the one who accepted.
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        try {
            $delivery = $this->deliveryService->acceptDelivery($id, $request->user()->id);

            return response()->json([
                'message' => 'Delivery accepted successfully.',
                'data'    => $delivery,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Reject delivery
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $delivery = $this->deliveryService->rejectDelivery($id, $validated['reason']);

            return response()->json([
                'message' => 'Delivery rejected.',
                'data'    => $delivery,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get pending deliveries
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $deliveries = $this->deliveryService->getPendingDeliveries($perPage);

        return response()->json($deliveries);
    }

    /**
     * Get delivery statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->deliveryService->getDeliveryStatistics();

        return response()->json(['data' => $statistics]);
    }
}
