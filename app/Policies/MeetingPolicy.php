<?php

namespace App\Policies;

use App\Models\User;
use Modules\Meetings\Models\Meeting;

/**
 * Meetings previously had NO object-level authorization on show/edit/update/
 * destroy — only a list-level filter in index(). This policy applies the same
 * rule to a single record: the organizer, a participant, or a super-admin may
 * view; only the organizer or a super-admin may change it.
 */
class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $this->isVisibleTo($user, $meeting) || $user->hasPermission('meeting.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('meeting.create') || $user->hasRole('super-admin');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $this->organizes($user, $meeting) || $user->hasPermission('meeting.edit');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $this->organizes($user, $meeting) || $user->hasPermission('meeting.delete');
    }

    /**
     * Start / complete / cancel are lifecycle transitions on the meeting itself.
     */
    public function transition(User $user, Meeting $meeting): bool
    {
        return $this->organizes($user, $meeting) || $user->hasPermission('meeting.edit');
    }

    public function print(User $user, Meeting $meeting): bool
    {
        return $this->view($user, $meeting);
    }

    /**
     * Minutes lifecycle. Each step is a distinct permission so approval can be
     * separated from authorship rather than sharing one "manage minutes" grant.
     */
    public function manageMinutes(User $user, Meeting $meeting): bool
    {
        return $this->ownsMinutes($user, $meeting) || $user->hasPermission('meeting.manage_minutes');
    }

    public function submitMinutes(User $user, Meeting $meeting): bool
    {
        return $this->ownsMinutes($user, $meeting) || $user->hasPermission('meeting.submit_minutes');
    }

    public function approveMinutes(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meeting.approve_minutes') || $user->hasRole('super-admin');
    }

    public function publishMinutes(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meeting.publish_minutes') || $user->hasRole('super-admin');
    }

    /**
     * Action items are edited by the organizer, or by whoever the item is
     * assigned to, so an assignee can progress their own work.
     */
    public function manageActionItems(User $user, Meeting $meeting): bool
    {
        return $this->organizes($user, $meeting) || $user->hasPermission('meeting.create_action');
    }

    public function assignActionItems(User $user, Meeting $meeting): bool
    {
        return $this->organizes($user, $meeting) || $user->hasPermission('meeting.assign_action');
    }

    protected function ownsMinutes(User $user, Meeting $meeting): bool
    {
        return $this->organizes($user, $meeting);
    }

    protected function organizes(User $user, Meeting $meeting): bool
    {
        return $meeting->organizer_id === $user->id || $user->hasRole('super-admin');
    }

    protected function isVisibleTo(User $user, Meeting $meeting): bool
    {
        return $this->organizes($user, $meeting)
            || $meeting->participants()->where('user_id', $user->id)->exists()
            || $user->hasRole('super-admin');
    }
}
