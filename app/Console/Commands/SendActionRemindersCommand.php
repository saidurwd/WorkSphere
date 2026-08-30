<?php

namespace App\Console\Commands;

use App\Jobs\SendActionReminderJob;
use App\Models\MeetingActionItem;
use Illuminate\Console\Command;

class SendActionRemindersCommand extends Command
{
    protected $signature = 'actions:remind
                            {--days=3 : Number of days before due date to send reminders}';

    protected $description = 'Send reminder notifications for action items due soon';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->addDays($days)->endOfDay();

        $actionItems = MeetingActionItem::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $cutoff)
            ->where('due_date', '>=', now()->startOfDay())
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('assigned_to')
            ->with('assignedTo')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($actionItems as $actionItem) {
            if (! $actionItem->assignedTo || ! $actionItem->assignedTo->email) {
                $skipped++;

                continue;
            }

            SendActionReminderJob::dispatch($actionItem);
            $sent++;
        }

        $this->info("Action reminders dispatched: {$sent}.");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} action items due to missing assignee or email.");
        }

        return 0;
    }
}
