<?php

namespace App\Console\Commands;

use App\Jobs\SendTaskReminderJob;
use App\Models\Task;
use Illuminate\Console\Command;

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
