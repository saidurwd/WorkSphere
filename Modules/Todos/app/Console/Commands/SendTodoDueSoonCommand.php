<?php

namespace Modules\Todos\Console\Commands;

use App\Enums\NotificationType;
use Carbon\CarbonImmutable;
use Modules\Todos\Jobs\SendTodoDueSoonJob;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\NotifiedFilter;

/**
 * Warns ahead of a due date.
 *
 * The window is inclusive of today and the next `--days` days, so an item due
 * tomorrow appears on one run and not on the one before it.
 */
class SendTodoDueSoonCommand extends SendsTodoNotifications
{
    protected $signature = 'todos:due-soon
                            {--days=2 : How many days ahead to look}
                            {--limit= : Maximum To-Dos to process in one pass}';

    protected $description = 'Notify assignees about To-Dos due soon. Safe to run repeatedly.';

    protected function notificationType(): NotificationType
    {
        return NotificationType::TodoDueSoon;
    }

    /**
     * Per-day, so a To-Do that stays due for three days warns on each of them
     * rather than collapsing into a single notification.
     */
    protected function jobClass(): string
    {
        return SendTodoDueSoonJob::class;
    }

    protected function discriminator(): string
    {
        return now()->toDateString();
    }

    protected function days(): int
    {
        return min(30, max(0, (int) $this->option('days')));
    }

    protected function describeWindow(): string
    {
        return now()->toDateString().' to '.CarbonImmutable::parse(now()->toDateString())->addDays($this->days())->toDateString();
    }

    protected function query()
    {
        // The cheap half of idempotency: skip work already warned about today.
        // The authoritative guard is the dedupe key, applied when the job runs.
        $filtered = NotifiedFilter::apply(
            Todo::query(),
            now()->toDateString(),
        );

        $from = now()->toDateString();
        $to = CarbonImmutable::parse($from)->addDays($this->days())->toDateString();

        return $filtered
            ->dueBetween($from, $to)
            ->when($this->option('limit'), fn ($query) => $query->limit((int) $this->option('limit')));
    }
}
