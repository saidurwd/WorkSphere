<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Meetings\Models\Meeting;

/**
 * Meetings the viewer organises or attends, from today forward.
 */
class UpcomingMeetingsWidget implements DashboardWidget
{
    use Concerns;

    public function key(): string
    {
        return 'upcoming_meetings';
    }

    public function label(): string
    {
        return 'Upcoming Meetings';
    }

    public function icon(): string
    {
        return 'calendar-week';
    }

    public function group(): string
    {
        return 'personal';
    }

    public function permissions(): array
    {
        return ['meeting.view'];
    }

    public function cacheTtl(): int
    {
        return 120;
    }

    /**
     * Scheduled meetings from today onward that the viewer organises or attends,
     * capped — the date is the sort key so the cap takes the nearest ones.
     *
     * Rows are mapped to arrays rather than returned as models, like every other
     * widget here. A model cannot survive the cache store: `config/cache.php`
     * sets `serializable_classes => false`, so it comes back out of the database
     * store as `__PHP_Incomplete_Class` and the second dashboard view of any user
     * dies on the first method call — or, for this one, inside `route()`.
     */
    public function resolve(User $user): Collection
    {
        return $this->visibleMeetings($user)
            ->where('status', 'scheduled')
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->orderBy('meeting_date')
            ->limit(6)
            ->get(['id', 'title', 'meeting_date', 'start_time', 'location'])
            ->map(fn (Meeting $meeting): array => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'date' => $meeting->meeting_date?->format('M d, Y'),
                'time' => $meeting->start_time?->format('H:i'),
                'location' => $meeting->location,
                'url' => route('meetings.show', $meeting->id),
            ]);
    }

    /**
     * `resolve()` returns a Collection, so the registry caches it as a plain
     * array and re-wraps it on the way out.
     */
    public function isListValued(): bool
    {
        return true;
    }
}
