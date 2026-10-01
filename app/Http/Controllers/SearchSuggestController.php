<?php

namespace App\Http\Controllers;

use App\Search\SearchableEntity;
use App\Search\SearchIndex;
use App\Search\SearchService;
use App\Support\StatusBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Type-ahead endpoint for the navbar search box.
 *
 * Returns JSON rather than an HTML fragment so the browser cannot be tricked
 * into executing a record's title: the client inserts each title with
 * `textContent`, never `innerHTML`. That matters because the title is
 * user-authored text from five different modules.
 *
 * Permissions are enforced in {@see SearchService::search()}, exactly as they
 * are on the full search page — this endpoint is not a shortcut around them.
 * That is also why a term matching nothing returns an empty list rather than an
 * error: the caller cannot distinguish "no matches" from "no matches you may
 * see", and that is the intended behaviour.
 */
class SearchSuggestController extends Controller
{
    /**
     * How many hits per group in the dropdown. Small on purpose: this is a
     * shortcut to the full page, not a replacement for it.
     */
    private const PER_GROUP = 4;

    public function __construct(private readonly SearchService $search) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in(SearchIndex::keys())],
        ]);

        $term = trim((string) $validated['q']);

        $groups = $this->search->search(
            $request->user(),
            $term,
            ['modules' => $validated['modules'] ?? []],
        );

        return response()->json([
            'term' => $term,
            'total' => $groups->sum('count'),
            'groups' => $groups->take(4)->map(fn (array $group): array => [
                'key' => $group['entity']->key,
                'label' => $group['entity']->label,
                'count' => $group['count'],
                'results' => $group['rows']->take(self::PER_GROUP)->map(fn ($row): array => [
                    'title' => (string) $row->{$group['entity']->titleColumn},
                    'url' => $group['entity']->urlFor($row),
                    // Optional line: a status or date, already formatted. Kept
                    // plain so the client only ever renders text.
                    'meta' => $this->meta($group['entity'], $row),
                ])->values(),
            ])->values(),
            'seeAllUrl' => route('search', ['q' => $term]),
        ]);
    }

    protected function meta(SearchableEntity $entity, mixed $row): ?string
    {
        if ($entity->dateColumn !== null && $row->{$entity->dateColumn} !== null) {
            return Carbon::parse($row->{$entity->dateColumn})->format('M d, Y');
        }

        if ($entity->statusColumn !== null && $row->{$entity->statusColumn} !== null) {
            return StatusBadge::label($row->{$entity->statusColumn});
        }

        return null;
    }
}
