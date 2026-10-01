<?php

namespace Modules\Todos\Services;

use App\Enums\LinkType;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\Obligation;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoLink;

/**
 * To-Do link management — DATABASE-ARCHITECTURE.md §4.4, §7.2.
 *
 * SECURITY: `linkable_type` is never taken from request input. It is resolved
 * through {@see ALLOWED_LINKABLES}, an explicit map of the concrete model classes
 * a To-Do may point at. A user-supplied type string reaching a morphTo() would
 * let a caller name any class in the application and have it resolved, which is
 * an information-disclosure primitive on its own.
 *
 * Each entry also declares the permission required to see the target. A link is
 * only ever rendered when the viewer passes the target's own policy — a To-Do
 * pointing at a Task the viewer cannot open must not leak its title.
 */
class TodoLinkService
{
    /**
     * morph key => [model class, permission to view one].
     *
     * @var array<string, array{class-string<Model>, string}>
     */
    private const ALLOWED_LINKABLES = [
        'task' => [Task::class, 'task.view'],
        'meeting' => [Meeting::class, 'meeting.view'],
        'meeting_action_item' => [MeetingActionItem::class, 'meeting.view'],
        'obligation' => [Obligation::class, 'obligation.view'],
        'project' => [Project::class, 'project.view'],
        'todo' => [Todo::class, 'todos.view_all'],
    ];

    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * The morph keys a caller may name.
     *
     * @return list<string>
     */
    public static function linkableTypes(): array
    {
        return array_keys(self::ALLOWED_LINKABLES);
    }

    /**
     * Resolve a morph key to its model class, or refuse.
     *
     * @return class-string<Model>
     */
    public function resolveType(string $morphKey): string
    {
        $class = self::ALLOWED_LINKABLES[$morphKey][0] ?? null;

        if ($class === null) {
            throw InvalidArgumentException(sprintf(
                'Unknown linkable type [%s]. Allowed: %s.',
                $morphKey,
                implode(', ', self::linkableTypes()),
            ));
        }

        return $class;
    }

    public function attach(
        User $actor,
        Todo $todo,
        string $morphKey,
        int $targetId,
        LinkType $linkType = LinkType::Related,
    ): TodoLink {
        $class = $this->resolveType($morphKey);

        // Resolve the target before linking, so a link to a deleted or absent
        // record fails here rather than rendering as a dangling reference.
        $target = $class::query()->find($targetId);

        if ($target === null) {
            throw InvalidArgumentException(sprintf(
                'Cannot link to %s #%d: no such record.',
                $class,
                $targetId,
            ));
        }

        return DB::transaction(function () use ($todo, $class, $target, $linkType): TodoLink {
            $link = TodoLink::query()->firstOrCreate([
                'todo_id' => $todo->id,
                'linkable_type' => $class,
                'linkable_id' => $target->getKey(),
                'link_type' => $linkType,
            ]);

            $this->activity->record(
                Todo::class,
                $todo,
                'link_attached',
                null,
                [
                    'link_id' => $link->getKey(),
                    'link_type' => $linkType->value,
                    'target_type' => class_basename($class),
                    'target_id' => $target->getKey(),
                ],
            );

            return $link;
        });
    }

    public function detach(User $actor, Todo $todo, int $linkId): void
    {
        DB::transaction(function () use ($todo, $linkId): void {
            $link = $todo->links()->whereKey($linkId)->first();

            if ($link === null) {
                throw InvalidArgumentException('That link does not belong to this To-Do.');
            }

            $attributes = [
                'link_id' => $link->getKey(),
                'link_type' => $link->link_type->value,
                'target_type' => class_basename($link->linkable_type),
                'target_id' => $link->linkable_id,
            ];

            $link->delete();

            $this->activity->record(Todo::class, $todo, 'link_detached', $attributes, null);
        });
    }

    /**
     * To-Dos pointing at a record — the reverse direction, served by
     * `todo_link_reverse_idx`.
     *
     * Scoped to the viewer: a reverse link whose target the viewer cannot open
     * must not be listed, or the list itself leaks the target's existence.
     *
     * @return Collection<int, Todo>
     */
    public function resolveReverse(User $viewer, string $morphKey, int $targetId): Collection
    {
        $class = $this->resolveType($morphKey);
        $permission = self::ALLOWED_LINKABLES[$morphKey][1];

        if (! $viewer->hasPermission($permission) && ! $viewer->hasPermission('todos.view_all')) {
            return collect();
        }

        return Todo::query()
            ->whereHas('links', function ($query) use ($class, $targetId): void {
                $query->where('linkable_type', $class)
                    ->where('linkable_id', $targetId);
            })
            ->get();
    }

    /**
     * Linked records the viewer is allowed to see, ready to render.
     *
     * @return Collection<int, array{link: TodoLink, target: Model}>
     */
    public function visibleTargets(User $viewer, Todo $todo): Collection
    {
        return $todo->links
            ->map(function (TodoLink $link) use ($viewer): ?array {
                $permission = self::ALLOWED_LINKABLES[$this->morphKeyFor($link->linkable_type)][1] ?? null;

                if ($permission === null) {
                    return null;
                }

                // A super-admin keeps the todos.view_all bypass; anyone else must
                // hold the target module's view permission outright.
                if (! $viewer->hasPermission('todos.view_all') && ! $viewer->hasPermission($permission)) {
                    return null;
                }

                $target = $link->linkable;

                return $target === null ? null : ['link' => $link, 'target' => $target];
            })
            ->filter()
            ->values();
    }

    protected function morphKeyFor(string $class): ?string
    {
        foreach (self::ALLOWED_LINKABLES as $key => [$allowed]) {
            if ($allowed === $class) {
                return $key;
            }
        }

        return null;
    }
}
