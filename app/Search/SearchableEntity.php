<?php

namespace App\Search;

/**
 * One searchable entity, declared once.
 *
 * Everything the search service, the UI and the tests need to know about an
 * entity lives here: which model, which fields are searched, which permission
 * governs seeing it, and where a hit links to. Adding an entity is a single
 * `register()` call — see {@see SearchIndex::defaults()}.
 */
final class SearchableEntity
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  list<string>  $fields  Columns a term is matched against.
     * @param  string  $permission  Permission required to see a hit at all.
     * @param  string  $route  Named route a hit links to.
     * @param  string  $label  Group heading in the UI.
     * @param  string  $titleColumn  Column rendered as the hit's title.
     * @param  string|null  $statusColumn  Column the status facet filters on.
     * @param  string|null  $ownerColumn  Column the owner facet filters on.
     * @param  string|null  $dateColumn  Column the date-range facet filters on.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $model,
        public readonly array $fields,
        public readonly string $permission,
        public readonly string $route,
        public readonly string $label,
        public readonly string $titleColumn = 'title',
        public readonly ?string $statusColumn = 'status',
        public readonly ?string $ownerColumn = null,
        public readonly ?string $dateColumn = null,
    ) {}

    /**
     * Whether the user may see hits from this entity.
     *
     * Checked per entity, not once for the page: a user without
     * `obligation.view` must not receive obligation rows *or a count that reveals
     * how many exist*.
     */
    public function isVisibleTo(\App\Models\User $user): bool
    {
        return $user->hasPermission($this->permission);
    }

    /**
     * The model behind a hit, for the detail link.
     */
    public function urlFor(mixed $record): ?string
    {
        $route = $this->route;

        if (! \Illuminate\Support\Facades\Route::has($route)) {
            return null;
        }

        return route($route, $record);
    }

    /**
     * A short excerpt with the match highlighted, so a hit can be judged without
     * opening it.
     */
    public function excerpt(mixed $record, string $term, int $length = 160): string
    {
        $source = (string) ($record->description ?? '');

        if (trim($source) === '') {
            return '';
        }

        $excerpt = \Illuminate\Support\Str::limit($source, $length);

        if ($term === '') {
            return $excerpt;
        }

        return preg_replace(
            '/('.preg_quote($term, '/').')/iu',
            '<mark>$1</mark>',
            $excerpt,
        ) ?? $excerpt;
    }
}
