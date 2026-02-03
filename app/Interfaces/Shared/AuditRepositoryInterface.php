<?php

namespace App\Interfaces\Shared;

use App\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AuditRepositoryInterface
{
    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator;

    /**
     * Get audit logs by user
     */
    public function getAuditLogsByUser(int $userId, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get audit logs by module
     */
    public function getAuditLogsByModule(string $module, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get audit logs for a specific entity
     */
    public function getAuditLogsBySubject(string $type, int $id): Collection;

    /**
     * Get single audit log by ID
     */
    public function getAuditLogById(int $id): ?Activity;

    /**
     * Get audit statistics
     */
    public function getAuditStatistics(array $filters = []): array;
}
