<?php

namespace App\Http\Controllers;

use App\Support\NotificationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * The notification centre — GAP-021.
 *
 * Replaces the per-module log screens as far as the navbar is concerned. Reads
 * `notifications`, so "unread" means a row with no `read_at`, which the previous
 * three-log merge could never express: those tables record deliveries, not
 * receipts, and nothing in them could be marked read.
 *
 * The per-module log screens still exist — they are an administrative record of
 * what the system tried to send, including failures, which this view deliberately
 * does not show.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->whereIn('type', NotificationPresenter::NOTIFICATION_CLASSES)
            ->paginate(25);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()
                ->unreadNotifications()
                ->whereIn('type', NotificationPresenter::NOTIFICATION_CLASSES)
                ->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $resolved = $this->resolve($request, $notification);

        if ($resolved !== null && $resolved->read_at === null) {
            $resolved->markAsRead();
        }

        return back()->with('success', 'Marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->whereIn('type', NotificationPresenter::NOTIFICATION_CLASSES)
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Resolve a notification id to a row belonging to THIS user.
     *
     * Scoped by `notifiable_id`: without it, any authenticated user could mark
     * (or read) anybody else's notification by guessing a UUID.
     */
    protected function resolve(Request $request, string $notification): ?DatabaseNotification
    {
        return $request->user()
            ->notifications()
            ->whereKey($notification)
            ->whereIn('type', NotificationPresenter::NOTIFICATION_CLASSES)
            ->first();
    }
}
