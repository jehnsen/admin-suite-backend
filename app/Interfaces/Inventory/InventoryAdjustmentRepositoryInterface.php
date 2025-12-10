<?php

namespace App\Interfaces\Inventory;

use App\Models\InventoryAdjustment;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryAdjustmentRepositoryInterface
{
    public function getAllAdjustments(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getAdjustmentById(int $id): ?InventoryAdjustment;
    public function createAdjustment(array $data): InventoryAdjustment;
    public function updateAdjustment(int $id, array $data): InventoryAdjustment;
    public function deleteAdjustment(int $id): bool;
    public function approveAdjustment(int $id, int $approvedBy): InventoryAdjustment;
    public function rejectAdjustment(int $id, string $reason): InventoryAdjustment;
    public function getPendingAdjustments(int $perPage = 15): LengthAwarePaginator;
    public function getAdjustmentStatistics(): array;
}
