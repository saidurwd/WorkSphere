<?php

namespace App\Policies;

use App\Models\User;
use Modules\Meetings\Models\MeetingActionItem;

/**
 * Action items inherit visibility from their parent meeting, but may also be
 * progressed by the user they are assigned to. Without this an assignee could
 * see their own item in the list yet be unable to open it.
 */
class MeetingActionItemPolicy
{
    public function view(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('view', $actionItem->meeting)
            || $actionItem->assigned_to === $user->id
            || $user->can('view_all_actions');
    }

    public function create(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('create_action', $actionItem->meeting);
    }

    public function update(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('manage_action_items', $actionItem->meeting)
            || $actionItem->assigned_to === $user->id;
    }

    public function delete(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('manage_action_items', $actionItem->meeting);
    }

    /**
     * Linking a Task to an action item is an assignment decision, so it is
     * gated separately from a plain edit.
     */
    public function linkTask(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('assign_action_items', $actionItem->meeting);
    }
}
