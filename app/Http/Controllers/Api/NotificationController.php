<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get all active notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->notificationService->getForUser($request->user())
        );
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($uuid, $request->user());

        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json(['message' => "{$count} notification(s) marked as read."]);
    }

    /**
     * Dismiss a notification (hides it permanently for this user).
     */
    public function dismiss(Request $request, string $uuid): JsonResponse
    {
        $notification = $this->notificationService->dismiss($uuid, $request->user());

        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json(['message' => 'Notification dismissed.']);
    }
}
