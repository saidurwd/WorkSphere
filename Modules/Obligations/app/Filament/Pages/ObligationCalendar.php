<?php

namespace Modules\Obligations\Filament\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;

/**
 * A month grid of obligation expiries and the due dates of tasks filed against
 * obligations. Both lists are limited to what the viewer may see.
 */
class ObligationCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Obligation calendar';

    protected static ?int $navigationSort = 34;

    protected static ?string $title = 'Obligation calendar';

    protected static ?string $slug = 'obligation-calendar';

    protected string $view = 'filament.obligation-calendar';

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

        $expiries = Obligation::query()
            ->visibleTo($user)
            ->whereDate('expiry_date', '>=', $start->toDateString())
            ->whereDate('expiry_date', '<=', $end->toDateString())
            ->get(['id', 'title', 'expiry_date', 'risk_level'])
            ->groupBy(fn (Obligation $o): string => $o->expiry_date->toDateString());

        $tasks = Task::query()
            ->whereNotNull('obligation_id')
            ->whereIn('obligation_id', Obligation::query()->visibleTo($user)->select('obligations.id'))
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->get(['id', 'title', 'due_date', 'status'])
            ->groupBy(fn (Task $t): string => $t->due_date->toDateString());

        return [
            'anchor' => $anchor,
            'days' => collect(range(0, (int) $start->diffInDays($end)))->map(fn (int $offset): CarbonImmutable => $start->addDays($offset)),
            'expiries' => $expiries,
            'tasks' => $tasks,
            'today' => now()->toDateString(),
        ];
    }
}
