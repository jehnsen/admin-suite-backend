<?php

namespace App\Http\Controllers\Api\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\TagDeliveryAssetsRequest;
use App\Models\Delivery;
use App\Services\Inventory\AssetTaggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DeliveryAssetTaggingController extends Controller
{
    protected $assetTaggingService;

    public function __construct(AssetTaggingService $assetTaggingService)
    {
        $this->assetTaggingService = $assetTaggingService;
    }

    /**
     * Get pending assets that need tagging for a delivery
     */
    public function getPendingAssets(int $deliveryId): JsonResponse
    {
        try {
            $delivery = Delivery::with(['items.inventoryItems'])->findOrFail($deliveryId);

            // Get all inventory items from this delivery that are equipment and need asset tagging
            $pendingAssets = $delivery->items
                ->flatMap(function ($deliveryItem) {
                    return $deliveryItem->inventoryItems->map(function ($inventoryItem) use ($deliveryItem) {
                        return [
                            'inventory_item_id' => $inventoryItem->id,
                            'item_code' => $inventoryItem->item_code,
                            'item_name' => $inventoryItem->item_name,
                            'category' => $inventoryItem->category,
                            'serial_number' => $inventoryItem->serial_number,
                            'property_number' => $inventoryItem->property_number,
                            'needs_tagging' => $this->assetTaggingService->requiresAssetTagging($inventoryItem->category)
                                && empty($inventoryItem->property_number),
                        ];
                    });
                })
                ->filter(fn($asset) => $asset['needs_tagging'])
                ->values();

            return response()->json([
                'success' => true,
                'data' => $pendingAssets,
                'meta' => [
                    'delivery_id' => $deliveryId,
                    'delivery_receipt_number' => $delivery->delivery_receipt_number,
                    'total_pending' => $pendingAssets->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending assets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tag assets with serial numbers and property numbers
     */
    public function tagAssets(TagDeliveryAssetsRequest $request, int $deliveryId): JsonResponse
    {
        try {
            $delivery = Delivery::findOrFail($deliveryId);

            if ($delivery->status !== 'Accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only accepted deliveries can be tagged',
                ], 400);
            }

            DB::transaction(function () use ($request) {
                foreach ($request->asset_details as $assetData) {
                    $this->assetTaggingService->updateAssetTagging(
                        $assetData['inventory_item_id'],
                        [
                            'serial_number' => $assetData['serial_number'] ?? null,
                            'property_number' => $assetData['property_number']
                                ?? $this->assetTaggingService->generatePropertyNumber(),
                        ]
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Assets tagged successfully',
                'data' => [
                    'delivery_id' => $deliveryId,
                    'tagged_count' => count($request->asset_details),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to tag assets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
