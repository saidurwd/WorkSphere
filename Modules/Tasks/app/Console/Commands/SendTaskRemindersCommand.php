<?php

namespace Modules\Tasks\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tasks\Jobs\SendTaskReminderJob;
use Modules\Tasks\Models\Task;

class SendTaskRemindersCommand extends Command
{
    protected $signature = 'tasks:remind
                            {--days=3 : Number of days before due date to send reminders}';

    protected $description = 'Send reminder notifications for tasks due soon';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->addDays($days)->endOfDay();

        $tasks = Task::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $cutoff)
            ->where('due_date', '>=', now()->startOfDay())
            // `active()`, for the same reason as the overdue command: a literal
            // `pending|in_progress` silently excluded `on_hold` and would have
            // excluded `postponed`, so nobody was reminded about work they had
            // deferred rather than finished.
            ->active()
            ->whereNotNull('responsible_user_id')
            ->with('responsibleUser')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($tasks as $task) {
            if (! $task->responsibleUser || ! $task->responsibleUser->email) {
                $skipped++;

                continue;
            }

            SendTaskReminderJob::dispatch($task);
            $sent++;
        }

        $this->info("Task reminders dispatched: {$sent}.");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} tasks due to missing responsible user or email.");
        }

        return 0;
    }
}
