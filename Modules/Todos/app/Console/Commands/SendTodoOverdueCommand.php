<?php

namespace Modules\Todos\Console\Commands;

use App\Enums\NotificationType;
use Modules\Todos\Jobs\SendTodoOverdueJob;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\NotifiedFilter;

/**
 * Warns about To-Dos past their due date.
 *
 * Re-running is safe: the discriminator is the run date, so a second run on the
 * same day produces the identical dedupe key and sends nothing. That is what
 * makes this safe to schedule — and safe to run by hand after a deploy.
 */
class SendTodoOverdueCommand extends SendsTodoNotifications
{
    protected $signature = 'todos:overdue
                            {--limit= : Maximum To-Dos to process in one pass}';

    protected $description = 'Notify assignees about overdue To-Dos. Safe to run repeatedly.';

    protected function notificationType(): NotificationType
    {
        return NotificationType::TodoOverdue;
    }

    protected function jobClass(): string
    {
        return SendTodoOverdueJob::class;
    }

    protected function discriminator(): string
    {
        return now()->toDateString();
    }

    protected function describeWindow(): string
    {
        return 'due before '.now()->toDateString();
    }

    protected function query()
    {
        // The cheap half of idempotency: skip work already warned about today.
        // The authoritative guard is the dedupe key, applied when the job runs.
        $filtered = NotifiedFilter::apply(
            Todo::query(),
            now()->toDateString(),
        );

        return $filtered
            ->overdue()
            ->when($this->option('limit'), fn ($query) => $query->limit((int) $this->option('limit')));
    }
}
