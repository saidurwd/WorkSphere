<?php

namespace App\Dashboard\Widgets;

use App\Dashboard\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Collection;

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
     * Returns models, not presentation arrays: the dashboard view already knows
     * how to render a Meeting, and a widget that also formats is a widget whose
     * output only one template can use.
     */
    public function resolve(User $user): Collection
    {
        return $this->visibleMeetings($user)
            ->where('status', 'scheduled')
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->orderBy('meeting_date')
            ->limit(6)
            ->get(['id', 'title', 'meeting_date', 'start_time', 'location']);
    }
}
