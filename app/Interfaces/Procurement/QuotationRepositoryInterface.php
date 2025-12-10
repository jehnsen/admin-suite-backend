<?php

namespace App\Interfaces\Procurement;

use App\Models\Quotation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface QuotationRepositoryInterface
{
    /**
     * Get all quotations with optional filters and pagination
     */
    public function getAllQuotations(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single quotation by ID with items
     */
    public function getQuotationById(int $id): ?Quotation;

    /**
     * Get quotations by purchase request ID
     */
    public function getQuotationsByPurchaseRequest(int $prId): Collection;

    /**
     * Create a new quotation with items
     */
    public function createQuotation(array $data): Quotation;

    /**
     * Update a quotation
     */
    public function updateQuotation(int $id, array $data): Quotation;

    /**
     * Delete a quotation (soft delete)
     */
    public function deleteQuotation(int $id): bool;

    /**
     * Mark quotation as selected
     */
    public function selectQuotation(int $id): Quotation;

    /**
     * Get selected quotation for a purchase request
     */
    public function getSelectedQuotation(int $prId): ?Quotation;

    /**
     * Evaluate and rank quotations for a purchase request
     */
    public function evaluateQuotations(int $prId, array $evaluationData): bool;

    /**
     * Calculate quotation totals from items
     */
    public function calculateTotals(int $id): Quotation;
}
