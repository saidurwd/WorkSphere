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
        // A watcher sees the task. They do NOT thereby gain update or delete
        // rights — `owns()` is deliberately left free of the watcher check, so
        // visibility and authorship stay separate.
        return $this->owns($user, $task)
            || $user->hasPermission('task.view')
            || $this->watch($user, $task)
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

    /**
     * A watcher sees the task but gains no editing rights over it — mirroring
     * `TodoWatcher` and §4.1's `view` clause.
     */
    public function watch(User $user, Task $task): bool
    {
        return $task->watchers()->where('user_id', $user->id)->exists();
    }

    /**
     * Managing a task's own watcher list. Separate from `update` because adding
     * somebody else to a task is a different act from editing its content.
     */
    public function manageWatchers(User $user, Task $task): bool
    {
        return $this->owns($user, $task) || $user->hasPermission('task.update');
    }

    /**
     * Logging time against a task. The person who did the work logs the time, so
     * a watcher cannot log minutes for somebody else.
     */
    public function logTime(User $user, Task $task): bool
    {
        return $task->responsible_user_id === $user->id
            || $task->user_id === $user->id
            || $user->hasPermission('task.update');
    }

    /**
     * Creating a sub-task. Re-parenting someone else's work is not allowed just
     * because you may edit your own.
     */
    public function createSubtask(User $user, Task $parent): bool
    {
        return $this->update($user, $parent);
    }

    /**
     * Re-parenting a task under a different parent. Guarded separately from
     * `update` because a move changes the whole ancestry of the subtree.
     */
    public function reparent(User $user, Task $task): bool
    {
        return $this->owns($user, $task) || $user->hasPermission('task.update');
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
