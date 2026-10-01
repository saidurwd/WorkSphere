<?php

namespace Modules\Todos\Policies;

use App\Models\User;
use Modules\Todos\Models\Todo;

/**
 * To-Do authorization — TODO-MODULE-SPECIFICATION.md §4.1, implemented literally.
 *
 *   view(user, todo)   = todos.view_all
 *                      OR todo.assignee_id = user.id
 *                      OR todo.creator_id  = user.id
 *                      OR user watches the todo
 *                      OR (visibility = Team AND user.department_id = todo.department_id)
 *
 *   update(user, todo) = (todos.update_own AND (assignee OR creator))
 *                      OR (todos.update_any AND view)
 *                      OR todo.creator_id = user.id
 *
 *   delete(user, todo) = todos.delete AND (creator OR todos.update_any)
 *   assign(user, todo) = todos.assign AND (creator OR assignee OR todos.update_any)
 *   restore(user, todo)= todos.restore AND (creator OR todos.update_any)
 *
 * `view` is an explicit method, never a fallback to `can()`. The three existing
 * modules scope their lists inside controllers and check nothing per record;
 * To-Dos get it right from day one, and TodoScope mirrors the same predicate so
 * the list and the object check cannot disagree.
 *
 * Note the asymmetry that the spec calls for: the creator may always amend their
 * own To-Do, but may not always delete it — deletion additionally requires
 * `todos.delete`. That is deliberate and is not an oversight.
 */
class TodoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('todos.create');
    }

    /**
     * Delegating a To-Do to somebody else at creation is a distinct permission
     * from creating one at all (§4). Without it, any user who may create a To-Do
     * could also assign it into somebody else's workload.
     */
    public function createForOthers(User $user): bool
    {
        return $user->hasPermission('todos.create_for_others') || $user->hasRole('super-admin');
    }

    public function view(User $user, Todo $todo): bool
    {
        if ($user->hasPermission('todos.view_all')) {
            return true;
        }

        if ($todo->assignee_id === $user->id || $todo->creator_id === $user->id) {
            return true;
        }

        if ($todo->watchers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $this->isTeamVisibleTo($user, $todo);
    }

    public function update(User $user, Todo $todo): bool
    {
        if ($todo->creator_id === $user->id) {
            return true;
        }

        if ($user->hasPermission('todos.update_any') && $this->view($user, $todo)) {
            return true;
        }

        return $user->hasPermission('todos.update_own')
            && ($todo->assignee_id === $user->id || $todo->creator_id === $user->id);
    }

    public function delete(User $user, Todo $todo): bool
    {
        if (! $user->hasPermission('todos.delete')) {
            return false;
        }

        return $todo->creator_id === $user->id || $user->hasPermission('todos.update_any');
    }

    public function assign(User $user, Todo $todo): bool
    {
        if (! $user->hasPermission('todos.assign')) {
            return false;
        }

        return $todo->creator_id === $user->id
            || $todo->assignee_id === $user->id
            || $user->hasPermission('todos.update_any');
    }

    public function restore(User $user, Todo $todo): bool
    {
        if (! $user->hasPermission('todos.restore')) {
            return false;
        }

        return $todo->creator_id === $user->id || $user->hasPermission('todos.update_any');
    }

    public function comment(User $user, Todo $todo): bool
    {
        return $user->hasPermission('todos.comment') && $this->view($user, $todo);
    }

    public function complete(User $user, Todo $todo): bool
    {
        // Completing is an update of the record's state, so it inherits `update`.
        return $this->update($user, $todo);
    }

    public function reopen(User $user, Todo $todo): bool
    {
        return $this->update($user, $todo);
    }

    public function archive(User $user, Todo $todo): bool
    {
        return $this->update($user, $todo);
    }

    public function manageRecurrence(User $user, Todo $todo): bool
    {
        return $user->hasPermission('todos.manage_recurrence') && $this->view($user, $todo);
    }

    /**
     * Team visibility only widens the audience when the To-Do actually names a
     * department. A Team To-Do with no department would otherwise match every
     * unassigned viewer in the system.
     */
    protected function isTeamVisibleTo(User $user, Todo $todo): bool
    {
        if (! $todo->isTeamVisible()) {
            return false;
        }

        $departmentId = $user->employee?->department_id;

        return $departmentId !== null && (int) $departmentId === $todo->department_id;
    }
}
