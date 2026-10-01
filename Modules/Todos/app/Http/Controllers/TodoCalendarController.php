<?php

namespace Modules\Todos\Http\Controllers;

use App\Enums\CalendarView;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Todos\Models\Todo;

/**
 * Month and week views built from `due_date`.
 *
 * Read-only. The grid is assembled server-side from the dates that actually have
 * work, using the same scoped query as the list — so the calendar cannot show
 * work the viewer is not allowed to see, which a client-side calendar library fed
 * by an unscoped endpoint would happily do.
 */
class TodoCalendarController extends Controller
{
    public function __invoke(Request $request): View
    {
        $view = CalendarView::parse($request->input('view'));

        $anchor = $request->filled('date')
            ? CarbonImmutable::parse((string) $request->input('date'))->startOfDay()
            : CarbonImmutable::parse(now()->toDateString())->startOfDay();

        [$start, $end] = match ($view) {
            CalendarView::Week => [
                $anchor->startOfWeek(),
                $anchor->endOfWeek(),
            ],
            CalendarView::Month => [
                $anchor->startOfMonth()->startOfWeek(),
                $anchor->endOfMonth()->endOfWeek(),
            ],
        };

        $byDate = Todo::query()
            ->active()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->with(['assignee:id,name'])
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn (Todo $todo): string => $todo->due_date->toDateString());

        return view('todos.calendar', [
            'calendarView' => $view,
            'anchor' => $anchor,
            'days' => $this->daysFor($view, $anchor),
            'byDate' => $byDate,
            'previous' => $anchor->modify($view === CalendarView::Week ? '-1 week' : '-1 month')->toDateString(),
            'next' => $anchor->modify($view === CalendarView::Week ? '+1 week' : '+1 month')->toDateString(),
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    protected function daysFor(CalendarView $view, CarbonImmutable $anchor): Collection
    {
        if ($view === CalendarView::Week) {
            return collect(range(0, 6))
                ->map(fn (int $offset): CarbonImmutable => $anchor->startOfWeek()->addDays($offset));
        }

        return collect(range(1, $anchor->daysInMonth))
            ->map(fn (int $day): CarbonImmutable => $anchor->startOfMonth()->addDays($day - 1));
    }
}
