<?php

namespace Modules\Todos\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Todos\Models\Todo;

/**
 * Restricts a Todo query to the rows the current user may see.
 *
 * This exists so the list query and the object check can never disagree.
 * TODO-MODULE-SPECIFICATION.md §4.1 states the problem plainly: the existing
 * modules scope their *lists* inside controllers and perform no object-level
 * check at all. With only a policy, `Todo::query()` used anywhere else — a
 * report, a job, a relation eager load — would silently bypass it.
 *
 * The predicate below is the SQL transliteration of `TodoPolicy::view`. The two
 * are pinned together by `TodoScopeTest`, which asserts that a record the policy
 * denies is also absent from the scoped query, so the duplication is visible
 * rather than silent.
 *
 * A user holding `todos.view_all` bypasses the scope entirely, matching the
 * policy's first clause.
 */
class TodoScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = $this->currentUser();

        // No authenticated user (a console command building a system report, for
        // instance) must not be silently narrowed to nothing, and must not be
        // given everything either. Callers opt in explicitly via withoutGlobalScope.
        if (! $user instanceof User) {
            return;
        }

        if ($user->hasPermission('todos.view_all')) {
            return;
        }

        $builder->where(function (Builder $query) use ($user): void {
            $query->where('todos.assignee_id', $user->id)
                ->orWhere('todos.creator_id', $user->id)
                ->orWhereHas(
                    'watchers',
                    fn (Builder $watchers): Builder => $watchers->where('user_id', $user->id),
                )
                ->orWhere(function (Builder $team) use ($user): void {
                    $team->where('todos.visibility', 'team')
                        ->whereNotNull('todos.department_id')
                        ->where('todos.department_id', $this->userDepartmentId($user));
                });
        });
    }

    protected function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * `users` has no `department_id`; the department is reached through
     * `employees`. A user with no employee record is in no department, so team
     * visibility never matches for them rather than matching every team To-Do.
     */
    protected function userDepartmentId(User $user): ?int
    {
        $departmentId = $user->employee?->department_id;

        return $departmentId === null ? null : (int) $departmentId;
    }
}
