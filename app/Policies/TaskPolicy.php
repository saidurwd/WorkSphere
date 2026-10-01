<?php

namespace App\Policies;

use App\Models\User;
use Modules\Tasks\Models\Task;

/**
 * Formalises the ad-hoc `authorizeTask()` check that previously lived in
 * TaskController:378-393. The rule granularity is unchanged — an owner or
 * responsible user may act on the task, and a super-admin may act on any.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->owns($user, $task)
            || $user->hasPermission('task.view')
            || $this->belongsToVisibleMeeting($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('task.create') || $user->hasRole('super-admin');
    }

    public function update(User $user, Task $task): bool
    {
        return $this->owns($user, $task) || $user->hasPermission('task.update');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('task.delete') || $user->hasRole('super-admin');
    }

    public function transfer(User $user, Task $task): bool
    {
        return $this->owns($user, $task) || $user->hasPermission('task.transfer');
    }

    public function addRemark(User $user, Task $task): bool
    {
        return $this->owns($user, $task) || $user->hasPermission('task.view');
    }

    protected function owns(User $user, Task $task): bool
    {
        return $task->user_id === $user->id
            || $task->responsible_user_id === $user->id
            || $user->hasRole('super-admin');
    }

    /**
     * A task created from a meeting action item belongs, for visibility purposes,
     * to the meeting that produced it.
     */
    protected function belongsToVisibleMeeting(User $user, Task $task): bool
    {
        return $task->meetingActionItems()
            ->whereHas('meeting', function ($query) use ($user) {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('participants', fn ($participants) => $participants->where('user_id', $user->id));
            })
            ->exists();
    }
}
