<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Modules\Todos\Notifications\TodoNotification;

/**
 * Turns a Laravel notification row into what the navbar renders — GAP-021.
 *
 * The navbar reads `notifications` rather than the three per-module log tables,
 * which means it now receives a payload rather than a log row. The icon, colour
 * and link used to be inferred from a `notification_type` string by three
 * `str_contains` checks in the Blade template; that inference is kept here, and
 * pinned by tests, rather than left as string matching in a view.
 *
 * Notification payloads are untrusted input as far as rendering is concerned:
 * `title` and `url` come out of the `data` JSON column, which anything with a
 * database connection can write. They are returned as strings for `{{ }}`
 * interpolation and are never treated as markup.
 */
final class NotificationPresenter
{
    /**
     * Notification classes the navbar shows.
     *
     * Anything else in the table is somebody else's concern and is not rendered
     * in the global bell.
     *
     * @var list<class-string>
     */
    public const NOTIFICATION_CLASSES = [
        TodoNotification::class,
    ];

    /**
     * @return array{title: string, url: string, icon: string, background: string, timeAgo: string, unread: bool}
     */
    public static function present(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        $title = (string) ($data['title'] ?? 'Notification');
        $type = (string) ($data['type'] ?? '');
        $todoId = isset($data['todo_id']) ? (int) $data['todo_id'] : null;

        return [
            'title' => $title,
            // route() rather than a URL carried in the payload: a stored URL is
            // attacker-controllable and would turn the bell into an open redirect.
            'url' => $todoId === null || ! self::hasTodoRoute()
                ? route('dashboard.index')
                : route('todos.show', $todoId),
            'icon' => self::iconFor($type),
            'background' => self::backgroundFor($type),
            'timeAgo' => $notification->created_at?->diffForHumans() ?? '',
            'unread' => $notification->read_at === null,
        ];
    }

    protected static function iconFor(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'todo') => 'bi-check2-square',
            str_contains($type, 'task') => 'bi-check2-square',
            str_contains($type, 'meeting') => 'bi-calendar-week',
            str_contains($type, 'obligation') => 'bi-file-earmark-text',
            default => 'bi-bell',
        };
    }

    protected static function backgroundFor(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'todo') => 'bg-primary',
            str_contains($type, 'task') => 'bg-primary',
            str_contains($type, 'meeting') => 'bg-success',
            str_contains($type, 'obligation') => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    /**
     * The To-Do routes are only reachable once the module is enabled. Rendering
     * them unconditionally would break the navbar on an install without it.
     */
    protected static function hasTodoRoute(): bool
    {
        return Route::has('todos.show');
    }
}
