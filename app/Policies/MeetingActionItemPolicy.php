<?php

namespace App\Policies;

use App\Models\User;
use Modules\Meetings\Models\MeetingActionItem;

/**
 * Action items inherit visibility from their parent meeting, but may also be
 * progressed by the user they are assigned to. Without this an assignee could
 * see their own item in the list yet be unable to open it.
 *
 * Every delegation below names the PARENT policy's ability in camelCase —
 * `manageActionItems`, `assignActionItems`. It previously used snake_case
 * equivalents (`manage_action_items`, `create_action`, `assign_action_items`,
 * `view_all_actions`), none of which is a method on `MeetingPolicy` and none of
 * which the Gate can resolve. Every one of those delegations therefore evaluated
 * to false, so for any account without the `super-admin` role — which bypasses
 * the Gate before a policy is ever consulted — creating, deleting and linking a
 * meeting action item were all impossible. The seeded permission
 * `meeting.view_all_actions` is a permission, not an ability, so the visibility
 * clause now reads it directly.
 */
class MeetingActionItemPolicy
{
    public function view(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('view', $actionItem->meeting)
            || $actionItem->assigned_to === $user->id
            || $user->hasPermission('meeting.view_all_actions');
    }

    public function create(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('manageActionItems', $actionItem->meeting);
    }

    public function update(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('manageActionItems', $actionItem->meeting)
            || $actionItem->assigned_to === $user->id;
    }

    public function delete(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('manageActionItems', $actionItem->meeting);
    }

    /**
     * Linking a Task to an action item is an assignment decision, so it is
     * gated separately from a plain edit.
     */
    public function linkTask(User $user, MeetingActionItem $actionItem): bool
    {
        return $user->can('assignActionItems', $actionItem->meeting);
    }
}
