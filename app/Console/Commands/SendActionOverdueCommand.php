<?php

namespace App\Console\Commands;

use App\Jobs\SendActionOverdueJob;
use App\Models\MeetingActionItem;
use Illuminate\Console\Command;

class SendActionOverdueCommand extends Command
{
    protected $signature = 'actions:overdue';

    protected $description = 'Send overdue notifications for action items past their due date';

    public function handle(): int
    {
        $actionItems = MeetingActionItem::query()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
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

            SendActionOverdueJob::dispatch($actionItem);
            $sent++;
        }

        $this->info("Overdue action notifications dispatched: {$sent}.");
        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} overdue action items due to missing assignee or email.");
        }

        return 0;
    }
}
