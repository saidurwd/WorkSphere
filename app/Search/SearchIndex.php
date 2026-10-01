<?php

namespace App\Search;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The registry of searchable entities — GAP-035.
 *
 * Adding an entity is one call to {@see register()}. Nothing else in the
 * application needs to know it exists: the controller iterates the registry, the
 * UI renders a group per entry, and the facet list is derived from the entries'
 * declared columns.
 *
 * The registry is keyed and order-stable, so a result group always appears in the
 * same place regardless of which entries the user can see.
 */
final class SearchIndex
{
    /**
     * @var array<string, SearchableEntity>
     */
    private static array $entities = [];

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  list<string>  $fields
     */
    public static function register(
        string $key,
        string $model,
        array $fields,
        string $permission,
        string $route,
        string $label,
        string $titleColumn = 'title',
        ?string $statusColumn = 'status',
        ?string $ownerColumn = null,
        ?string $dateColumn = null,
    ): void {
        self::$entities[$key] = new SearchableEntity(
            key: $key,
            model: $model,
            fields: $fields,
            permission: $permission,
            route: $route,
            label: $label,
            titleColumn: $titleColumn,
            statusColumn: $statusColumn,
            ownerColumn: $ownerColumn,
            dateColumn: $dateColumn,
        );
    }

    /**
     * @return Collection<string, SearchableEntity>
     */
    public static function all(): Collection
    {
        self::ensureDefaults();

        return collect(self::$entities);
    }

    public static function get(string $key): ?SearchableEntity
    {
        return self::all()->get($key);
    }

    /**
     * Only the entities this user is allowed to see results from.
     *
     * @return Collection<string, SearchableEntity>
     */
    public static function forUser(User $user): Collection
    {
        return self::all()->filter(
            fn (SearchableEntity $entity): bool => $entity->isVisibleTo($user),
        );
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return self::all()->keys()->all();
    }

    /**
     * Declared once, here, rather than scattered across module providers — a
     * registry whose contents depend on provider load order is not a registry.
     *
     * @var array<string, array<string, mixed>>
     */
    private static function defaults(): array
    {
        return [
            'todo' => [
                'model' => \Modules\Todos\Models\Todo::class,
                'fields' => ['title', 'description'],
                'permission' => 'todos.view',
                'route' => 'todos.show',
                'label' => 'To-Dos',
                'titleColumn' => 'title',
                'statusColumn' => 'status',
                'ownerColumn' => 'assignee_id',
                'dateColumn' => 'due_date',
            ],
            'task' => [
                'model' => \Modules\Tasks\Models\Task::class,
                'fields' => ['title', 'description'],
                'permission' => 'task.view',
                'route' => 'tasks.show',
                'label' => 'Tasks',
                'titleColumn' => 'title',
                'statusColumn' => 'status',
                'ownerColumn' => 'responsible_user_id',
                'dateColumn' => 'due_date',
            ],
            'meeting' => [
                'model' => \Modules\Meetings\Models\Meeting::class,
                'fields' => ['title', 'description'],
                'permission' => 'meeting.view',
                'route' => 'meetings.show',
                'label' => 'Meetings',
                'titleColumn' => 'title',
                'statusColumn' => 'status',
                'ownerColumn' => 'organizer_id',
                'dateColumn' => 'meeting_date',
            ],
            'obligation' => [
                'model' => \Modules\Obligations\Models\Obligation::class,
                'fields' => ['title', 'description'],
                'permission' => 'obligation.view',
                'route' => 'obligations.show',
                'label' => 'Obligations',
                'titleColumn' => 'title',
                'statusColumn' => 'status',
                'ownerColumn' => 'owner_user_id',
                'dateColumn' => 'expiry_date',
            ],
            'project' => [
                'model' => \Modules\Projects\Models\Project::class,
                'fields' => ['name', 'description'],
                'permission' => 'project.view',
                'route' => 'projects.show',
                'label' => 'Projects',
                'titleColumn' => 'name',
                // A project has no status or owner column, so it offers no facet
                // for either — declaring one would render a filter that does
                // nothing.
                'statusColumn' => null,
                'ownerColumn' => null,
                'dateColumn' => null,
            ],
        ];
    }

    private static function ensureDefaults(): void
    {
        if (self::$entities !== []) {
            return;
        }

        foreach (self::defaults() as $key => $definition) {
            self::register($key, ...array_values($definition));
        }
    }

    /**
     * Test seam: drop the registry so a test can register a throwaway entity.
     */
    public static function flush(): void
    {
        self::$entities = [];
    }
}
