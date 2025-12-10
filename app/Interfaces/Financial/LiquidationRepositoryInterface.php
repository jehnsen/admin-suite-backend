<?php

namespace App\Interfaces\Financial;

use App\Models\Liquidation;
use Illuminate\Pagination\LengthAwarePaginator;

interface LiquidationRepositoryInterface
{
    public function getAllLiquidations(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getLiquidationById(int $id): ?Liquidation;
    public function createLiquidation(array $data): Liquidation;
    public function updateLiquidation(int $id, array $data): Liquidation;
    public function deleteLiquidation(int $id): bool;
    public function verifyLiquidation(int $id, int $verifiedBy, ?string $remarks = null): Liquidation;
    public function approveLiquidation(int $id, int $approvedBy): Liquidation;
    public function rejectLiquidation(int $id, string $reason): Liquidation;
    public function getPendingLiquidations(int $perPage = 15): LengthAwarePaginator;
    public function getLiquidationStatistics(): array;
}
