<?php

namespace App\Dashboard\Widgets;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;

/**
 * Shared query fragments for the dashboard widgets.
 *
 * Each returns a base query already narrowed to the work a user may see, using
 * the same rule the corresponding module's own list uses. Centralised so a
 * widget cannot quietly widen what its module's index page hides.
 */
trait Concerns
{
    protected function visibleTasks(User $user): Builder
    {
        return Task::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('responsible_user_id', $user->id)
                    ->orWhereHas('watchers', fn ($watchers): mixed => $watchers->where('user_id', $user->id));
            });
    }

    protected function visibleTodos(User $user): Builder
    {
        return Todo::query()
            ->where(function ($query) use ($user): void {
                $query->where('assignee_id', $user->id)
                    ->orWhere('creator_id', $user->id)
                    ->orWhereHas('watchers', fn ($watchers): mixed => $watchers->where('user_id', $user->id));
            });
    }

    protected function visibleMeetings(User $user): Builder
    {
        return Meeting::query()
            ->where(function ($query) use ($user): void {
                $query->where('organizer_id', $user->id)
                    ->orWhereHas('participants', fn ($p): mixed => $p->where('user_id', $user->id));
            });
    }

    protected function visibleObligations(User $user): Builder
    {
        return Obligation::query()
            ->where(function ($query) use ($user): void {
                $query->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', fn ($r): mixed => $r
                        ->where('user_id', $user->id)
                        ->where('active', true));
            });
    }

    /**
     * Obligation statuses that mean "no longer outstanding".
     *
     * @return list<string>
     */
    protected function closedObligationStatuses(): array
    {
        return ['renewed', 'cancelled', 'not_required', 'archived'];
    }
}
