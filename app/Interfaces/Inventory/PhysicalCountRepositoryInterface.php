<?php

namespace App\Interfaces\Inventory;

use App\Models\PhysicalCount;
use Illuminate\Pagination\LengthAwarePaginator;

interface PhysicalCountRepositoryInterface
{
    public function getAllCounts(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getCountById(int $id): ?PhysicalCount;
    public function createCount(array $data): PhysicalCount;
    public function updateCount(int $id, array $data): PhysicalCount;
    public function verifyCount(int $id, int $verifiedBy): PhysicalCount;
    public function getCountsWithVariance(int $perPage = 15): LengthAwarePaginator;
    public function getCountStatistics(): array;
}
