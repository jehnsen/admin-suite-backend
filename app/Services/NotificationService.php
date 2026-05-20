<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NotificationService
{
    private const LOW_STOCK_THRESHOLD    = 5;
    private const BUDGET_ALERT_THRESHOLD = 85.0;

    /**
     * Sync live alerts into the notifications table, then return the active
     * (non-dismissed) notifications for the given user with read state.
     */
    public function getForUser(User $user): array
    {
        $this->syncLiveAlerts($user);

        $notifications = Notification::forUser($user->id)
            ->active()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($n) => $this->format($n));

        return [
            'data'         => $notifications->values()->toArray(),
            'total'        => $notifications->count(),
            'unread_count' => $notifications->filter(fn ($n) => !$n['is_read'])->count(),
        ];
    }

    public function markAsRead(string $uuid, User $user): ?Notification
    {
        $notification = Notification::where('uuid', $uuid)
            ->forUser($user->id)
            ->first();

        if ($notification && !$notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::forUser($user->id)
            ->active()
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function dismiss(string $uuid, User $user): ?Notification
    {
        $notification = Notification::where('uuid', $uuid)
            ->forUser($user->id)
            ->first();

        if ($notification) {
            $notification->update([
                'read_at'      => $notification->read_at ?? now(),
                'dismissed_at' => now(),
            ]);
        }

        return $notification;
    }

    /**
     * Sync live alerts: upsert new ones, dismiss resolved ones.
     */
    private function syncLiveAlerts(User $user): void
    {
        $live = collect()
            ->merge($this->pendingLeaveRequests())
            ->merge($this->lowStockAlerts())
            ->merge($this->budgetAlerts());

        $liveKeys = $live->pluck('subject_uuid')->filter()->all();

        // Upsert each live alert
        foreach ($live as $alert) {
            Notification::firstOrCreate(
                [
                    'user_id'      => $user->id,
                    'type'         => $alert['type'],
                    'subject_uuid' => $alert['subject_uuid'],
                ],
                [
                    'badge'        => $alert['badge'],
                    'title'        => $alert['title'],
                    'message'      => $alert['message'],
                    'subject_type' => $alert['subject_type'],
                    'meta'         => $alert['meta'],
                ]
            );
        }

        // Auto-dismiss notifications whose source record is no longer active
        Notification::forUser($user->id)
            ->active()
            ->whereIn('type', ['leave_pending', 'low_stock', 'budget_alert'])
            ->whereNotNull('subject_uuid')
            ->whereNotIn('subject_uuid', $liveKeys)
            ->update(['dismissed_at' => now()]);
    }

    private function format(Notification $n): array
    {
        return [
            'id'           => $n->uuid,
            'type'         => $n->type,
            'badge'        => $n->badge,
            'title'        => $n->title,
            'message'      => $n->message,
            'meta'         => $n->meta,
            'is_read'      => $n->isRead(),
            'read_at'      => $n->read_at?->toISOString(),
            'created_at'   => $n->created_at->toISOString(),
            'time_ago'     => $n->created_at->diffForHumans(),
        ];
    }

    // -------------------------------------------------------------------------
    // Live alert sources
    // -------------------------------------------------------------------------

    private function pendingLeaveRequests(): Collection
    {
        return LeaveRequest::with('employee')
            ->where('status', 'Pending')
            ->get()
            ->map(fn ($lr) => [
                'type'         => 'leave_pending',
                'badge'        => 'Pending',
                'title'        => 'Leave Request Pending',
                'message'      => ($lr->employee?->full_name ?? 'An employee') . ' submitted a leave request for approval',
                'subject_type' => LeaveRequest::class,
                'subject_uuid' => $lr->uuid,
                'meta'         => [
                    'leave_request_uuid' => $lr->uuid,
                    'employee_name'      => $lr->employee?->full_name,
                    'leave_type'         => $lr->leave_type,
                    'start_date'         => $lr->start_date,
                    'end_date'           => $lr->end_date,
                    'days_requested'     => $lr->days_requested,
                ],
            ]);
    }

    private function lowStockAlerts(): Collection
    {
        return InventoryItem::where('quantity', '<=', self::LOW_STOCK_THRESHOLD)
            ->where('status', '!=', 'Disposed')
            ->orderBy('quantity')
            ->get()
            ->map(fn ($item) => [
                'type'         => 'low_stock',
                'badge'        => 'Alert',
                'title'        => 'Low Stock Alert',
                'message'      => $item->item_name . ' is running low - only ' . $item->quantity . ' ' . strtolower($item->unit_of_measure ?? 'unit(s)') . ' left',
                'subject_type' => InventoryItem::class,
                'subject_uuid' => $item->uuid,
                'meta'         => [
                    'inventory_item_uuid' => $item->uuid,
                    'item_name'           => $item->item_name,
                    'quantity'            => $item->quantity,
                    'unit_of_measure'     => $item->unit_of_measure,
                    'threshold'           => self::LOW_STOCK_THRESHOLD,
                ],
            ]);
    }

    private function budgetAlerts(): Collection
    {
        return Budget::where('status', 'Active')
            ->whereRaw('(utilized_amount / NULLIF(allocated_amount, 0)) * 100 >= ?', [self::BUDGET_ALERT_THRESHOLD])
            ->orderByRaw('(utilized_amount / allocated_amount) DESC')
            ->get()
            ->map(function ($budget) {
                $pct = $budget->allocated_amount > 0
                    ? round(($budget->utilized_amount / $budget->allocated_amount) * 100, 1)
                    : 0;

                return [
                    'type'         => 'budget_alert',
                    'badge'        => 'Info',
                    'title'        => 'Budget Reminder',
                    'message'      => $pct . '% of ' . $budget->fund_source . ' budget has been utilized',
                    'subject_type' => Budget::class,
                    'subject_uuid' => $budget->uuid,
                    'meta'         => [
                        'budget_uuid'      => $budget->uuid,
                        'budget_name'      => $budget->budget_name,
                        'fund_source'      => $budget->fund_source,
                        'utilization_pct'  => $pct,
                        'utilized_amount'  => $budget->utilized_amount,
                        'allocated_amount' => $budget->allocated_amount,
                        'remaining'        => $budget->remaining_balance,
                    ],
                ];
            });
    }
}
