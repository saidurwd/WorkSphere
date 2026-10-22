<?php

namespace Modules\Todos\Filament\Pages;

use App\Enums\CalendarView;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Todos\Models\Todo;
use UnitEnum;

/**
 * Open To-Dos by due date, as a week or a month.
 *
 * The query runs through the Todo model, so `TodoScope` still narrows it to what
 * the viewer may see.
 */
class TodoCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'To-Dos';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'To-Do calendar';

    protected static ?string $slug = 'todo-calendar';

    protected string $view = 'filament.todo-calendar';

    public string $mode = 'month';

    public ?string $date = null;

    public function mount(): void
    {
        $this->mode = CalendarView::parse(request()->query('view'))->value;
        $this->date = $this->anchor()->toDateString();
    }

    public function setMode(string $mode): void
    {
        $this->mode = CalendarView::parse($mode)->value;
    }

    public function shift(int $direction): void
    {
        $unit = $this->mode === CalendarView::Week->value ? 'week' : 'month';

        $this->date = $this->anchor()->modify(($direction < 0 ? '-1 ' : '+1 ').$unit)->toDateString();
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    protected function anchor(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date ?? now()->toDateString())->startOfDay();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $anchor = $this->anchor();
        $week = $this->mode === CalendarView::Week->value;

        [$start, $end] = $week
            ? [$anchor->startOfWeek(), $anchor->endOfWeek()]
            : [$anchor->startOfMonth()->startOfWeek(), $anchor->endOfMonth()->endOfWeek()];

        /** @var Collection<string, Collection<int, Todo>> $byDate */
        $byDate = Todo::query()
            ->active()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->with('assignee:id,name')
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn (Todo $todo): string => $todo->due_date->toDateString());

        $days = collect(range(0, (int) $start->diffInDays($end)))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));

        return [
            'anchor' => $anchor,
            'days' => $days,
            'byDate' => $byDate,
            'today' => now()->toDateString(),
            'inMonth' => fn (CarbonImmutable $day): bool => $week || $day->month === $anchor->month,
        ];
    }
}
