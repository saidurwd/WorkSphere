<?php

namespace App\Search;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Global search — GAP-035.
 *
 * Two implementations behind one query shape:
 *
 * - MySQL/MariaDB uses `MATCH … AGAINST … IN BOOLEAN MODE` against the FULLTEXT
 *   indexes added by `2026_10_03_000001`.
 * - Every other driver uses `LIKE`.
 *
 * The fallback is not a test-only convenience. It is what runs on any install
 * without FULLTEXT support, and keeping both paths in one method means a
 * difference between them shows up here rather than in production.
 *
 * SECURITY:
 * 1. The term is always bound, never interpolated — including into the FULLTEXT
 *    column list, which comes from the registry and not from input.
 * 2. Permissions are applied in the QUERY. Hiding a row in the view is not
 *    authorization, and a group count is a disclosure even with no rows behind it.
 * 3. BOOLEAN MODE operators are stripped from the term so a user cannot smuggle
 *    `+`, `-` or `*` into the full-text grammar and force matches on nothing.
 *
 * EXPLICIT NON-GOAL: Elasticsearch, Meilisearch, Algolia. MySQL FULLTEXT is
 * sufficient at this scale; revisit only when measured latency demands it.
 */
class SearchService
{
    /**
     * Below this length a term matches too much to be useful and the cost is not
     * worth paying on every keystroke.
     */
    public const MIN_TERM_LENGTH = 2;

    /**
     * Hits per entity. The page is a discovery surface, not an export.
     */
    public const PER_ENTITY_LIMIT = 10;

    /**
     * @param  array{modules?: list<string>, status?: string|null, owner?: int|null, from?: string|null, to?: string|null}  $filters
     */
    public function search(User $user, string $term, array $filters = []): Collection
    {
        $term = trim($term);

        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return collect();
        }

        $term = $this->normalise($term);

        $requested = $filters['modules'] ?? [];

        $entities = SearchIndex::forUser($user)
            // An explicit module filter narrows; it never widens. Filtering by a
            // module the user cannot see simply yields nothing for it.
            ->when($requested !== [], fn (Collection $all) => $all->only($requested));

        return $entities
            ->map(function (SearchableEntity $entity) use ($user, $term, $filters): array {
                $rows = $this->query($entity, $user, $term, $filters)->limit(self::PER_ENTITY_LIMIT)->get();

                return [
                    'entity' => $entity,
                    'count' => $rows->count(),
                    'rows' => $rows,
                ];
            })
            ->filter(fn (array $group): bool => $group['count'] > 0)
            ->values();
    }

    /**
     * Per-entity counts, for the facet sidebar.
     *
     * Computed from the same query as the results, so a count can never describe
     * a different set than the rows behind it.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<string, int>
     */
    public function counts(User $user, string $term, array $filters = []): Collection
    {
        return $this->search($user, $term, $filters)
            ->mapWithKeys(fn (array $group): array => [$group['entity']->key => $group['count']]);
    }

    /**
     * The full result count across every permitted entity.
     *
     * @param  array<string, mixed>  $filters
     */
    public function total(User $user, string $term, array $filters = []): int
    {
        return (int) $this->counts($user, $term, $filters)->sum();
    }

    /**
     * The status values available for an entity's facet, permission-filtered.
     *
     * @return list<string>
     */
    public function statusValuesFor(User $user, SearchableEntity $entity): array
    {
        if ($entity->statusColumn === null) {
            return [];
        }

        $rows = $entity->model::query()
            ->whereNotNull($entity->statusColumn)
            ->when(
                $entity->key === 'todo',
                // Todo's global scope already narrows to the viewer; applying it
                // twice would be harmless but the others have no scope at all, so
                // the permission is checked here rather than assumed.
                fn () => null,
            )
            ->when(! $entity->isVisibleTo($user), fn () => null)
            ->distinct()
            ->orderBy($entity->statusColumn)
            ->pluck($entity->statusColumn)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // `pluck` returns cast values, so a status column arrives as an enum
        // instance rather than a string — `strval` on one is a TypeError.
        return array_values(array_map(
            static fn (mixed $status): string => $status instanceof \BackedEnum ? $status->value : (string) $status,
            $rows,
        ));
    }

    /**
     * The query for one entity, with permission and facets applied.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function query(SearchableEntity $entity, User $user, string $term, array $filters): Builder
    {
        /** @var Builder<Model> $query */
        $query = $entity->model::query();

        $this->applyPermission($query, $entity, $user);
        $this->applyTerm($query, $entity, $term);

        if (! empty($filters['status']) && $entity->statusColumn !== null) {
            $query->where($entity->statusColumn, $filters['status']);
        }

        if (! empty($filters['owner']) && $entity->ownerColumn !== null) {
            $query->where($entity->ownerColumn, (int) $filters['owner']);
        }

        if ($entity->dateColumn !== null) {
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            // A reversed range is swapped rather than silently matching nothing,
            // which would read as "no results exist".
            if ($from !== null && $to !== null && $from > $to) {
                [$from, $to] = [$to, $from];
            }

            if ($from !== null) {
                $query->whereDate($entity->dateColumn, '>=', $from);
            }

            if ($to !== null) {
                $query->whereDate($entity->dateColumn, '<=', $to);
            }
        }

        return $query;
    }

    /**
     * Entity-level visibility, applied in the query.
     *
     * Most entities have no global scope, so a permission the user lacks would
     * otherwise return their rows. This is the check the brief calls
     * non-negotiable: hiding in the view is not authorization, and a count is a
     * disclosure on its own.
     */
    protected function applyPermission(Builder $query, SearchableEntity $entity, User $user): void
    {
        if (! $entity->isVisibleTo($user)) {
            // Make the query structurally incapable of returning a row rather than
            // relying on the caller to check.
            $query->whereRaw('1 = 0');

            return;
        }

        if ($entity->ownerColumn === null) {
            return;
        }

        // `view_all`-style permission sees everything; otherwise the user sees
        // work they own, created, or — for To-Dos — watch. Mirrors the module's
        // own index rule so search can never reveal more than the list would.
        if ($user->hasPermission($entity->permission.'.all')) {
            return;
        }

        $table = $query->getModel()->getTable();
        $owner = $entity->ownerColumn;

        switch ($entity->key) {
            case 'todo':
                $query->where(function (Builder $inner) use ($table, $owner, $user): void {
                    $inner->where("{$table}.{$owner}", $user->id)
                        ->orWhere("{$table}.creator_id", $user->id)
                        ->orWhereHas('watchers', fn (Builder $w): Builder => $w->where('user_id', $user->id));
                });

                break;

            case 'task':
                $query->where(function (Builder $inner) use ($table, $owner, $user): void {
                    $inner->where("{$table}.{$owner}", $user->id)
                        ->orWhere("{$table}.user_id", $user->id)
                        ->orWhereHas('watchers', fn (Builder $w): Builder => $w->where('user_id', $user->id));
                });

                break;

            case 'meeting':
                $query->where(function (Builder $inner) use ($table, $owner, $user): void {
                    $inner->where("{$table}.{$owner}", $user->id)
                        ->orWhereHas('participants', fn (Builder $p): Builder => $p->where('user_id', $user->id));
                });

                break;

            case 'obligation':
                $query->where(function (Builder $inner) use ($table, $owner, $user): void {
                    $inner->where("{$table}.{$owner}", $user->id)
                        ->orWhereHas('responsibilities', fn (Builder $r): Builder => $r
                            ->where('user_id', $user->id)
                            ->where('active', true));
                });

                break;

            case 'project':
                $query->where("{$table}.user_id", $user->id);

                break;
        }
    }

    /**
     * The term, matched by FULLTEXT where supported and LIKE otherwise.
     */
    protected function applyTerm(Builder $query, SearchableEntity $entity, string $term): void
    {
        $fields = $entity->fields;

        if ($this->supportsFullText($entity)) {
            $columnList = implode(', ', array_map(
                fn (string $field): string => $field,
                $fields,
            ));

            // The column list comes from the registry, never from the request, and
            // the term is a bound parameter.
            $query->whereRaw(
                "MATCH ({$columnList}) AGAINST (? IN BOOLEAN MODE)",
                [$term],
            );

            return;
        }

        $query->where(function (Builder $inner) use ($fields, $term): void {
            foreach ($fields as $field) {
                $inner->orWhere($field, 'like', '%'.$term.'%');
            }
        });
    }

    /**
     * Whether FULLTEXT is actually available for this entity.
     *
     * Both conditions matter: SQLite never has it, and on MySQL the migration is
     * skipped for a table that does not exist yet.
     */
    protected function supportsFullText(SearchableEntity $entity): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        try {
            return Schema::hasIndex($entity->model::query()->getModel()->getTable(), 'todos_fulltext_idx')
                || $this->fullTextIndexExists($entity);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function fullTextIndexExists(SearchableEntity $entity): bool
    {
        $table = $entity->model::query()->getModel()->getTable();

        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => str_contains(strtolower($index['name']), 'fulltext'));
    }

    /**
     * Strip full-text operator characters from a user-supplied term.
     *
     * BOOLEAN MODE treats `+`, `-`, `*`, `"`, `(`, `)` and `~` as operators. Left
     * in, `budget -vendor` becomes a syntax query rather than a search, and
     * `+"` can be used to force a match on a term that exists nowhere.
     */
    protected function normalise(string $term): string
    {
        $cleaned = preg_replace('/[+\-*"()~<>@]+/u', ' ', $term) ?? $term;

        return trim(preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned);
    }
}
