<?php

namespace App\Repositories\Procurement;

use App\Interfaces\Procurement\QuotationRepositoryInterface;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuotationRepository implements QuotationRepositoryInterface
{
    public function getAllQuotations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Quotation::with(['purchaseRequest', 'supplier', 'items']);

        // Apply filters
        if (!empty($filters['purchase_request_id'])) {
            $query->where('purchase_request_id', $filters['purchase_request_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_selected'])) {
            $query->where('is_selected', $filters['is_selected']);
        }

        return $query->orderBy('quotation_date', 'desc')->paginate($perPage);
    }

    public function getQuotationById(int $id): ?Quotation
    {
        return Quotation::with(['purchaseRequest.items', 'supplier', 'items'])->find($id);
    }

    public function getQuotationsByPurchaseRequest(int $prId): Collection
    {
        return Quotation::with(['supplier', 'items'])
            ->where('purchase_request_id', $prId)
            ->orderBy('total_amount')
            ->get();
    }

    public function createQuotation(array $data): Quotation
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $quotation = Quotation::create($data);

            // Create quotation items
            foreach ($items as $index => $item) {
                $item['quotation_id'] = $quotation->id;
                $item['item_number'] = $index + 1;
                QuotationItem::create($item);
            }

            // Calculate totals
            $this->calculateTotals($quotation->id);

            return $quotation->fresh('items');
        });
    }

    public function updateQuotation(int $id, array $data): Quotation
    {
        return DB::transaction(function () use ($id, $data) {
            $quotation = Quotation::findOrFail($id);

            $items = $data['items'] ?? null;
            unset($data['items']);

            $quotation->update($data);

            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $quotation->items()->delete();

                // Create new items
                foreach ($items as $index => $item) {
                    $item['quotation_id'] = $quotation->id;
                    $item['item_number'] = $index + 1;
                    QuotationItem::create($item);
                }

                // Recalculate totals
                $this->calculateTotals($quotation->id);
            }

            return $quotation->fresh('items');
        });
    }

    public function deleteQuotation(int $id): bool
    {
        $quotation = Quotation::findOrFail($id);
        return $quotation->delete();
    }

    public function selectQuotation(int $id): Quotation
    {
        $quotation = Quotation::findOrFail($id);
        $quotation->markAsSelected();
        return $quotation->fresh();
    }

    public function getSelectedQuotation(int $prId): ?Quotation
    {
        return Quotation::with(['supplier', 'items'])
            ->where('purchase_request_id', $prId)
            ->where('is_selected', true)
            ->first();
    }

    public function evaluateQuotations(int $prId, array $evaluationData): bool
    {
        foreach ($evaluationData as $evaluation) {
            Quotation::where('id', $evaluation['quotation_id'])
                ->where('purchase_request_id', $prId)
                ->update([
                    'ranking'           => $evaluation['ranking'] ?? null,
                    'evaluation_score'  => $evaluation['evaluation_score'] ?? null,
                    'evaluation_remarks'=> $evaluation['remarks'] ?? null,
                    'status'            => 'Evaluated',
                ]);
        }

        return true;
    }

    public function calculateTotals(int $id): Quotation
    {
        $quotation = Quotation::findOrFail($id);

        $subtotal = $quotation->items()->sum('total_price');

        $totalAmount = $subtotal
            + $quotation->tax_amount
            + $quotation->shipping_cost
            - $quotation->discount_amount;

        $quotation->update([
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
        ]);

        return $quotation->fresh();
    }
}
