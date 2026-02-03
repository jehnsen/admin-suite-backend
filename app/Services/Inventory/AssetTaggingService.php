<?php

namespace App\Services\Inventory;

use App\Models\DeliveryItem;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class AssetTaggingService
{
    /**
     * Equipment categories that require asset tagging
     */
    private const EQUIPMENT_CATEGORIES = [
        'ICT Equipment',
        'Furniture',
        'Office Equipment',
        'Laboratory Equipment',
        'Sports Equipment',
    ];

    /**
     * Determine if an item requires asset tagging (property number, serial number)
     */
    public function requiresAssetTagging(string $category): bool
    {
        // Check exact match or contains "Equipment" or "Furniture"
        return in_array($category, self::EQUIPMENT_CATEGORIES)
            || str_contains($category, 'Equipment')
            || str_contains($category, 'Furniture');
    }

    /**
     * Generate unique property number following pattern: PROP-YYYY-XXXX
     */
    public function generatePropertyNumber(): string
    {
        $year = date('Y');

        $lastItem = InventoryItem::whereYear('created_at', $year)
            ->whereNotNull('property_number')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastItem && $lastItem->property_number) {
            $lastNumber = (int) substr($lastItem->property_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'PROP-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create InventoryItem from DeliveryItem
     */
    public function createInventoryItemFromDeliveryItem(
        DeliveryItem $deliveryItem,
        int $quantity = 1
    ): InventoryItem {
        $poItem = $deliveryItem->purchaseOrderItem;
        $delivery = $deliveryItem->delivery;

        // Determine category from PO item or default to "General Supplies"
        $category = $this->determineCategoryFromPOItem($poItem);

        // Generate unique item code
        $itemCode = $this->generateItemCode($category);

        $inventoryData = [
            'delivery_item_id' => $deliveryItem->id,
            'item_code' => $itemCode,
            'item_name' => $deliveryItem->item_description,
            'description' => $poItem->specifications ?? $deliveryItem->item_description,
            'category' => $category,
            'unit_of_measure' => $deliveryItem->unit_of_measure,
            'unit_cost' => $poItem->unit_price ?? 0,
            'quantity' => $quantity,
            'total_cost' => ($poItem->unit_price ?? 0) * $quantity,
            'brand' => $this->extractBrand($poItem->brand_model ?? ''),
            'model' => $this->extractModel($poItem->brand_model ?? ''),
            'fund_source' => $delivery->purchaseOrder->fund_source ?? 'MOOE',
            'supplier' => $delivery->supplier->supplier_name ?? null,
            'date_acquired' => $delivery->delivery_date ?? now(),
            'po_number' => $delivery->purchaseOrder->po_number ?? null,
            'invoice_number' => $delivery->delivery_receipt_number,
            'condition' => $deliveryItem->item_condition ?? 'Serviceable',
            'status' => 'In Stock',
            'location' => 'Warehouse',
            'remarks' => $deliveryItem->inspection_notes ?? "Received from delivery: {$delivery->delivery_receipt_number}",
        ];

        // For equipment, set estimated useful life
        if ($this->requiresAssetTagging($category)) {
            $inventoryData['estimated_useful_life_years'] = $this->getEstimatedUsefulLife($category);
            $inventoryData['book_value'] = $inventoryData['total_cost'];
        }

        return InventoryItem::create($inventoryData);
    }

    /**
     * Update asset tagging information (serial number and property number)
     */
    public function updateAssetTagging(int $inventoryItemId, array $assetData): InventoryItem
    {
        $inventoryItem = InventoryItem::findOrFail($inventoryItemId);

        $updateData = [];

        if (isset($assetData['serial_number'])) {
            $updateData['serial_number'] = $assetData['serial_number'];
        }

        if (isset($assetData['property_number'])) {
            $updateData['property_number'] = $assetData['property_number'];
        } elseif (empty($inventoryItem->property_number) && $this->requiresAssetTagging($inventoryItem->category)) {
            // Auto-generate property number if not provided and item is equipment
            $updateData['property_number'] = $this->generatePropertyNumber();
        }

        $inventoryItem->update($updateData);

        return $inventoryItem->fresh();
    }

    /**
     * Determine category from Purchase Order Item
     */
    private function determineCategoryFromPOItem($poItem): string
    {
        if (!$poItem) {
            return 'General Supplies';
        }

        $description = strtolower($poItem->item_description ?? '');

        // Try to match common keywords to categories
        if (str_contains($description, 'computer') || str_contains($description, 'printer') ||
            str_contains($description, 'laptop') || str_contains($description, 'scanner')) {
            return 'ICT Equipment';
        }

        if (str_contains($description, 'desk') || str_contains($description, 'chair') ||
            str_contains($description, 'cabinet') || str_contains($description, 'table')) {
            return 'Furniture';
        }

        if (str_contains($description, 'paper') || str_contains($description, 'pen') ||
            str_contains($description, 'marker') || str_contains($description, 'folder')) {
            return 'Office Supplies';
        }

        if (str_contains($description, 'laboratory') || str_contains($description, 'science')) {
            return 'Laboratory Equipment';
        }

        if (str_contains($description, 'sports') || str_contains($description, 'ball') ||
            str_contains($description, 'athletic')) {
            return 'Sports Equipment';
        }

        return 'General Supplies';
    }

    /**
     * Generate unique item code
     */
    private function generateItemCode(string $category): string
    {
        $prefix = match ($category) {
            'ICT Equipment' => 'ICT',
            'Furniture' => 'FUR',
            'Office Supplies' => 'OFC',
            'Laboratory Equipment' => 'LAB',
            'Sports Equipment' => 'SPT',
            default => 'GEN',
        };

        $year = date('Y');

        $lastItem = InventoryItem::where('item_code', 'like', $prefix . '-' . $year . '-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastItem) {
            $lastNumber = (int) substr($lastItem->item_code, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Extract brand from brand_model string
     */
    private function extractBrand(string $brandModel): ?string
    {
        if (empty($brandModel)) {
            return null;
        }

        // Assume brand is the first word
        $parts = explode(' ', $brandModel, 2);
        return $parts[0] ?? null;
    }

    /**
     * Extract model from brand_model string
     */
    private function extractModel(string $brandModel): ?string
    {
        if (empty($brandModel)) {
            return null;
        }

        // Assume model is everything after the first word
        $parts = explode(' ', $brandModel, 2);
        return $parts[1] ?? null;
    }

    /**
     * Get estimated useful life for equipment category
     */
    private function getEstimatedUsefulLife(string $category): int
    {
        return match ($category) {
            'ICT Equipment' => 5,
            'Furniture' => 10,
            'Office Equipment' => 7,
            'Laboratory Equipment' => 8,
            'Sports Equipment' => 5,
            default => 5,
        };
    }
}
