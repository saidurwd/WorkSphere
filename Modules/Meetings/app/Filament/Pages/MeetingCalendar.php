<?php

namespace Modules\Meetings\Filament\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use UnitEnum;

/**
 * A month grid of the meetings the viewer may see and the action items due.
 */
class MeetingCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Meetings';

    protected static ?string $navigationLabel = 'Meeting calendar';

    protected static ?int $navigationSort = 48;

    protected static ?string $title = 'Meeting calendar';

    protected static ?string $slug = 'meeting-calendar';

    protected string $view = 'filament.meeting-calendar';

    public ?string $date = null;

    public function mount(): void
    {
        $this->date = now()->startOfMonth()->toDateString();
    }

    public function shift(int $direction): void
    {
        $this->date = $this->anchor()->addMonths($direction < 0 ? -1 : 1)->toDateString();
    }

    public function today(): void
    {
        $this->date = now()->startOfMonth()->toDateString();
    }

    protected function anchor(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date ?? now()->toDateString())->startOfMonth();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $anchor = $this->anchor();
        $start = $anchor->startOfWeek();
        $end = $anchor->endOfMonth()->endOfWeek();
        $user = auth()->user();

        $meetings = Meeting::query()
            ->visibleTo($user)
            ->whereDate('meeting_date', '>=', $start->toDateString())
            ->whereDate('meeting_date', '<=', $end->toDateString())
            ->orderBy('start_time')
            ->get(['id', 'title', 'meeting_date', 'start_time', 'priority'])
            ->groupBy(fn (Meeting $m): string => $m->meeting_date->toDateString());

        // Action items follow the same rule as the register: only those the viewer
        // may see (the Blade calendar showed every item in the company).
        $actions = MeetingActionItem::query()
            ->visibleTo($user)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->get(['id', 'title', 'due_date', 'meeting_id'])
            ->groupBy(fn (MeetingActionItem $a): string => $a->due_date->toDateString());

        return [
            'anchor' => $anchor,
            'days' => collect(range(0, (int) $start->diffInDays($end)))->map(fn (int $offset): CarbonImmutable => $start->addDays($offset)),
            'meetings' => $meetings,
            'actions' => $actions,
            'today' => now()->toDateString(),
        ];
    }
}
