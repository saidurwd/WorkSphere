<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Search\SearchIndex;
use App\Search\SearchLogger;
use App\Search\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Global search — GAP-035.
 *
 * Every result group here is already permission-filtered by the time it reaches
 * the view; the template never decides what a user may see. That is the whole
 * reason the filtering lives in the query layer: a group count is a disclosure
 * even with no rows behind it.
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $search,
        private readonly SearchLogger $logger,
    ) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in(SearchIndex::keys())],
            'status' => ['nullable', 'string', 'max:30'],
            'owner' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        $filters = [
            'modules' => $validated['modules'] ?? [],
            'status' => $validated['status'] ?? null,
            'owner' => $validated['owner'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];

        $groups = $term === ''
            ? collect()
            : $this->search->search($request->user(), $term, $filters);

        $this->logger->record($request->user(), $term, $groups, $filters);

        return view('search.index', [
            'term' => $term,
            'groups' => $groups,
            'total' => $groups->sum('count'),
            'entities' => SearchIndex::forUser($request->user()),
            'statusValues' => $this->statusValues($request->user()),
            'filters' => $filters,
            'owners' => $this->owners(),
        ]);
    }

    /**
     * Status values for each entity's facet.
     *
     * Built from the same registry as the results, so an entity that cannot be
     * searched does not appear in the filter either.
     *
     * @return array<string, list<string>>
     */
    protected function statusValues(User $user): array
    {
        $values = [];

        foreach (SearchIndex::forUser($user) as $entity) {
            if ($entity->statusColumn === null) {
                continue;
            }

            $values[$entity->key] = $this->search->statusValuesFor($user, $entity);
        }

        return $values;
    }

    /**
     * Owner choices for the facet. Bounded: a select listing every account in a
     * large company is worse than a free-text filter.
     *
     * @return Collection<int, User>
     */
    protected function owners()
    {
        return User::query()->orderBy('name')->limit(200)->get(['id', 'name']);
    }
}
