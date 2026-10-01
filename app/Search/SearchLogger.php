<?php

namespace App\Search;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;

/**
 * Records what was searched for, and with what outcome.
 *
 * The point is relevance analysis: a term that returns nothing is either a typo
 * or a record the searcher cannot see, and those two look identical from the
 * outside. Aggregating the counts over time is the only way to tell them apart.
 *
 * Reuses `activity_logs` rather than adding a `search_logs` table: the shape is
 * the same (actor, action, subject, payload, timestamp) and a table per feature
 * is how Phase 8 removed two.
 *
 * The term is truncated, and the recorded payload holds only the term and the
 * per-group counts — never the matched records, which would copy user data into a
 * log that reports and exports do not treat as carefully as the source table.
 */
class SearchLogger
{
    /**
     * Terms shorter than this are noise; most are a single keystroke.
     */
    public const MIN_TERM_LENGTH = 3;

    /**
     * Terms longer than this are stored truncated.
     */
    public const MAX_TERM_LENGTH = 120;

    /**
     * @param  Collection<int, array{entity: SearchableEntity, count: int}>  $groups
     * @param  array<string, mixed>  $filters
     */
    public function record(User $user, string $term, Collection $groups, array $filters = []): void
    {
        $term = trim($term);

        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return;
        }

        $counts = $groups->mapWithKeys(
            fn (array $group): array => [$group['entity']->key => $group['count']],
        )->all();

        app(ActivityLogger::class)->record(
            'Search',
            null,
            'searched',
            null,
            [
                'term' => mb_substr($term, 0, self::MAX_TERM_LENGTH),
                'total' => array_sum($counts),
                'counts' => $counts,
                'filters' => array_filter([
                    'modules' => $filters['modules'] ?? [],
                    'status' => $filters['status'] ?? null,
                    'owner' => $filters['owner'] ?? null,
                    'from' => $filters['from'] ?? null,
                    'to' => $filters['to'] ?? null,
                ]),
            ],
            $user->id,
        );
    }
}
