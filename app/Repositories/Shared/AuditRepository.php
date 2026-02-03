<?php

namespace App\Repositories\Shared;

use App\Interfaces\Shared\AuditRepositoryInterface;
use App\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AuditRepository implements AuditRepositoryInterface
{
    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        // Filter by module
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        // Filter by event type (created, updated, deleted)
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        // Filter by user (causer)
        if (!empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Filter by log name
        if (!empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        // Filter by subject type (model)
        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', 'like', "%{$filters['subject_type']}%");
        }

        return $query->paginate($perPage);
    }

    /**
     * Get audit logs by user
     */
    public function getAuditLogsByUser(int $userId, int $perPage = 50): LengthAwarePaginator
    {
        return Activity::where('causer_id', $userId)
            ->with(['subject'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs by module
     */
    public function getAuditLogsByModule(string $module, int $perPage = 50): LengthAwarePaginator
    {
        return Activity::where('module', $module)
            ->with(['causer', 'subject'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs for a specific entity
     */
    public function getAuditLogsBySubject(string $type, int $id): Collection
    {
        return Activity::where('subject_type', $type)
            ->where('subject_id', $id)
            ->with('causer')
            ->latest()
            ->get();
    }

    /**
     * Get single audit log by ID
     */
    public function getAuditLogById(int $id): ?Activity
    {
        return Activity::with(['causer', 'subject'])->find($id);
    }

    /**
     * Get audit statistics
     */
    public function getAuditStatistics(array $filters = []): array
    {
        $query = Activity::query();

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Total activities
        $totalActivities = $query->count();

        // Group by event type
        $byEvent = (clone $query)
            ->groupBy('event')
            ->selectRaw('event, COUNT(*) as count')
            ->pluck('count', 'event')
            ->toArray();

        // Group by module
        $byModule = (clone $query)
            ->whereNotNull('module')
            ->groupBy('module')
            ->selectRaw('module, COUNT(*) as count')
            ->pluck('count', 'module')
            ->toArray();

        // Top 10 most active users
        $topUsers = (clone $query)
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->selectRaw('causer_id, COUNT(*) as activity_count')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->causer_id,
                    'user_name' => Activity::find($item->causer_id)?->causer?->name ?? 'Unknown',
                    'activity_count' => $item->activity_count,
                ];
            });

        // Recent activity trend (last 7 days)
        $recentTrend = Activity::whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total_activities' => $totalActivities,
            'by_event' => $byEvent,
            'by_module' => $byModule,
            'top_users' => $topUsers,
            'recent_trend' => $recentTrend,
        ];
    }
}
