<?php

namespace App\Console\Commands;

use App\Jobs\SendTaskOverdueJob;
use App\Models\Task;
use Illuminate\Console\Command;

class SendTaskOverdueCommand extends Command
{
    protected $signature = 'tasks:overdue';

    protected $description = 'Send overdue notifications for tasks past their due date';

    public function handle(): int
    {
        $tasks = Task::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->whereIn('status', ['pending', 'in_progress'])
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
