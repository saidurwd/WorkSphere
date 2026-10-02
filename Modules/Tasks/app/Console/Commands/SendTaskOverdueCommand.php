<?php

namespace Modules\Tasks\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tasks\Jobs\SendTaskOverdueJob;
use Modules\Tasks\Models\Task;

class SendTaskOverdueCommand extends Command
{
    protected $signature = 'tasks:overdue';

    protected $description = 'Send overdue notifications for tasks past their due date';

    public function handle(): int
    {
        $tasks = Task::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            // `active()` rather than a literal `pending|in_progress`: the list was
            // missing `on_hold`, so a task put on hold past its date was never
            // chased, and it would have gone on missing `postponed` too. Both are
            // open work. A task already completed or cancelled is not chased, which
            // is what the old list got right.
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

            SendTaskOverdueJob::dispatch($task);
            $sent++;
        }

        $this->info("Overdue task notifications dispatched: {$sent}.");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} overdue tasks due to missing responsible user or email.");
        }

        return 0;
    }
}
