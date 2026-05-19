<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Procurement\DeliveryResource;
use App\Services\Procurement\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'purchase_order_id', 'supplier_id', 'date_from', 'date_to']);
        $perPage = $this->getPerPage($request);

        $deliveries = $this->deliveryService->getAllDeliveries($filters, $perPage);

        return DeliveryResource::collection($deliveries);
    }

    public function show(string $uuid): JsonResponse
    {
        $id = \App\Models\Delivery::where('uuid', $uuid)->value('id') ?? 0;
        $delivery = $this->deliveryService->getDeliveryById($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found.'], 404);
        }

        return response()->json(['data' => new DeliveryResource($delivery)]);
    }

    public function byPurchaseOrder(Request $request, int $poId): AnonymousResourceCollection
    {
        $perPage = $this->getPerPage($request);
        $deliveries = $this->deliveryService->getDeliveriesByPurchaseOrder($poId, $perPage);

        return DeliveryResource::collection($deliveries);
    }

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
                'data'    => new DeliveryResource($delivery),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function update(UpdateDeliveryRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Delivery::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $delivery = $this->deliveryService->updateDelivery($id, $request->validated());

            return response()->json([
                'message' => 'Delivery updated successfully.',
                'data'    => new DeliveryResource($delivery),
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
        $id = \App\Models\Delivery::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $this->deliveryService->deleteDelivery($id);

            return response()->json(['message' => 'Delivery deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function inspect(InspectDeliveryRequest $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Delivery::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $inspectionData = [
                'inspected_by'       => $request->user()->id,
                'inspection_result'  => $request->input('inspection_result'),
                'inspection_remarks' => $request->input('inspection_remarks'),
            ];

            $delivery = $this->deliveryService->inspectDelivery($id, $inspectionData);

            return response()->json([
                'message' => 'Delivery inspected successfully.',
                'data'    => new DeliveryResource($delivery),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Record not found.'], 404);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    public function accept(Request $request, string $uuid): JsonResponse
    {
        $id = \App\Models\Delivery::where('uuid', $uuid)->value('id') ?? 0;
        try {
            $delivery = $this->deliveryService->acceptDelivery($id, $request->user()->id);

            return response()->json([
                'message' => 'Delivery accepted successfully.',
                'data'    => new DeliveryResource($delivery),
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
        $id = \App\Models\Delivery::where('uuid', $uuid)->value('id') ?? 0;
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $delivery = $this->deliveryService->rejectDelivery($id, $validated['reason']);

            return response()->json([
                'message' => 'Delivery rejected.',
                'data'    => new DeliveryResource($delivery),
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
        $deliveries = $this->deliveryService->getPendingDeliveries($perPage);

        return DeliveryResource::collection($deliveries);
    }

    public function statistics(): JsonResponse
    {
        $statistics = $this->deliveryService->getDeliveryStatistics();

        return response()->json(['data' => $statistics]);
    }
}
