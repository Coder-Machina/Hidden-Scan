<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user notifications and unread count.
     * GET /api/notifications
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
                'notifications' => [],
            ]);
        }

        $notifications = $user->notifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? 'new_chapter',
                    'title' => $n->data['title'] ?? 'Notification',
                    'message' => $n->data['message'] ?? '',
                    'url' => $n->data['url'] ?? '#',
                    'manga_id' => $n->data['manga_id'] ?? null,
                    'manga_title' => $n->data['manga_title'] ?? '',
                    'manga_slug' => $n->data['manga_slug'] ?? '',
                    'manga_cover' => $n->data['manga_cover'] ?? null,
                    'manga_type' => $n->data['manga_type'] ?? 'Manga',
                    'chapter_id' => $n->data['chapter_id'] ?? null,
                    'chapter_number' => $n->data['chapter_number'] ?? null,
                    'chapter_title' => $n->data['chapter_title'] ?? null,
                    'is_read' => $n->read_at !== null,
                    'created_at' => $n->created_at->toISOString(),
                    'created_at_human' => $n->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     * POST /api/notifications/{id}/read
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read for current user.
     * POST /api/notifications/mark-all-read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
