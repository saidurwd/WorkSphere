<?php

namespace Modules\Meetings\Listeners;

use Modules\Meetings\Events\ActionItemCompleted;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Services\MeetingActionTaskService;
use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Events\TaskUpdated;

/**
 * Keeps a meeting action item in step with the task it is linked to.
 *
 * When the task is finished, the action item it came from is finished too — and
 * announced as completed, once, exactly as if someone had ticked it on the meeting.
 */
class SyncActionItemsFromTask
{
    public function __construct(private readonly MeetingActionTaskService $actions) {}

    public function handle(TaskCompleted|TaskUpdated $event): void
    {
        MeetingActionItem::query()
            ->where('task_id', $event->task->getKey())
            ->get()
            ->each(function (MeetingActionItem $item): void {
                $before = $item->status;

                $this->actions->syncStatus($item);

                $item->refresh();

                if ($before !== 'completed' && $item->status === 'completed') {
                    event(new ActionItemCompleted($item));
                }
            });
    }
}
