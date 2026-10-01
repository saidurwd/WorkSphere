<?php

namespace Modules\Todos\Services;

use App\Enums\NotificationType;
use App\Models\User;
use Modules\Todos\Models\Todo;

/**
 * Who hears about a To-Do event — TODO-MODULE-SPECIFICATION.md §6.2.
 *
 * Kept in one place rather than inside each listener, because the interesting
 * cases are subtractions, not additions: nobody is told about their own action,
 * and a To-Do with no assignee must not notify its creator twice.
 *
 * The actor is excluded everywhere. Telling someone "you assigned this to
 * yourself" is noise, and for `TodoCompleted` it would be actively wrong.
 */
class RecipientResolver
{
    /**
     * @return list<User>
     */
    public function for(Todo $todo, NotificationType $type, ?int $actorId = null): array
    {
        $ids = match ($type) {
            NotificationType::TodoCreated => [$todo->assignee_id],
            NotificationType::TodoAssigned => [$todo->assignee_id],
            NotificationType::TodoReassigned => [$todo->assignee_id],
            NotificationType::TodoCompleted,
            NotificationType::TodoReopened => [$todo->creator_id, ...$this->watcherIds($todo)],
            NotificationType::TodoOverdue,
            NotificationType::TodoDueSoon,
            NotificationType::TodoReminder,
            NotificationType::TodoRecurringGenerated => [$todo->assignee_id],
            NotificationType::TodoCommented => [
                $todo->assignee_id,
                $todo->creator_id,
                ...$this->watcherIds($todo),
            ],
            NotificationType::TodoMentioned => [],
        };

        return $this->users(array_filter(array_unique($ids), fn (?int $id): bool => $id !== null), $actorId);
    }

    /**
     * Explicitly named recipients, for events where the audience is decided
     * elsewhere (a mention list, a reassignment's previous holder).
     *
     * @param  list<int|null>  $userIds
     * @return list<User>
     */
    public function users(array $userIds, ?int $actorId = null): array
    {
        $ids = array_values(array_unique(array_filter(
            $userIds,
            fn (?int $id): bool => $id !== null,
        )));

        if ($actorId !== null) {
            $ids = array_values(array_filter($ids, fn (int $id): bool => $id !== $actorId));
        }

        if ($ids === []) {
            return [];
        }

        return User::query()->whereIn('id', $ids)->get()->all();
    }

    /**
     * @return list<int>
     */
    protected function watcherIds(Todo $todo): array
    {
        return $todo->watchers()->pluck('user_id')->map(fn (mixed $id): int => (int) $id)->all();
    }
}
