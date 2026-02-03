<?php

namespace App\Services\Shared;

use App\Interfaces\Shared\AuditRepositoryInterface;
use App\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditService
{
    protected $auditRepository;

    public function __construct(AuditRepositoryInterface $auditRepository)
    {
        $this->auditRepository = $auditRepository;
    }

    /**
     * Get filtered audit logs
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->auditRepository->getAuditLogs($filters, $perPage);
    }

    /**
     * Get audit logs for a specific user
     */
    public function getUserActivity(int $userId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->auditRepository->getAuditLogsByUser($userId, $perPage);
    }

    /**
     * Get audit logs for a specific module
     */
    public function getModuleActivity(string $module, int $perPage = 50): LengthAwarePaginator
    {
        return $this->auditRepository->getAuditLogsByModule($module, $perPage);
    }

    /**
     * Get complete audit history for a specific entity
     */
    public function getEntityHistory(string $type, int $id): array
    {
        $logs = $this->auditRepository->getAuditLogsBySubject($type, $id);

        $modelName = class_basename($type);

        return [
            'entity_type' => $modelName,
            'entity_id' => $id,
            'total_changes' => $logs->count(),
            'history' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->description,
                    'changes' => [
                        'old' => $log->properties['old'] ?? [],
                        'new' => $log->properties['attributes'] ?? [],
                    ],
                    'performed_by' => [
                        'id' => $log->causer_id,
                        'name' => $log->causer?->name ?? 'System',
                    ],
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'module' => $log->module,
                    'performed_at' => $log->created_at->toISOString(),
                ];
            }),
        ];
    }

    /**
     * Get single audit log details
     */
    public function getAuditLogDetails(int $id): ?Activity
    {
        return $this->auditRepository->getAuditLogById($id);
    }

    /**
     * Generate audit report with statistics
     */
    public function generateAuditReport(array $filters = []): array
    {
        $statistics = $this->auditRepository->getAuditStatistics($filters);

        return [
            'report_generated_at' => now()->toISOString(),
            'filters_applied' => $filters,
            'statistics' => $statistics,
        ];
    }

    /**
     * Get audit logs for COA compliance export
     */
    public function exportAuditLogsForCOA(array $filters = []): array
    {
        $logs = $this->auditRepository->getAuditLogs($filters, 10000);

        return [
            'export_date' => now()->toDateString(),
            'total_records' => $logs->total(),
            'records' => $logs->items(),
        ];
    }
}
