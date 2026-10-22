<?php

namespace App\Filament\Workspace\Pages;

use App\Models\User;
use App\Search\SearchIndex;
use App\Search\SearchLogger;
use App\Search\SearchService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;

/**
 * Search across To-Dos, tasks, meetings, obligations and projects.
 *
 * What a person finds is decided by {@see SearchService}, which narrows every
 * module to what they may see. This page only asks the question and draws the
 * answer; the matched text comes back already escaped, with only the highlight
 * wrapper added, which is why it is the one place that prints markup.
 */
class Search extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Search';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Search';

    protected static ?string $slug = 'search';

    protected string $view = 'filament.workspace.search';

    /**
     * A search is a database query a person can repeat as fast as they can type, so
     * the page keeps the `search` limiter the old route had.
     *
     * @var string|array<int, string>
     */
    protected static string|array $routeMiddleware = ['throttle:search'];

    #[Url(as: 'q')]
    public string $term = '';

    /**
     * @var list<string>
     */
    #[Url]
    public array $modules = [];

    #[Url]
    public ?string $status = null;

    #[Url]
    public ?string $owner = null;

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public function mount(): void
    {
        $this->logSearch();
    }

    /**
     * Run the search the person asked for. Logged here, not on every re-render, so
     * the log counts searches rather than keystrokes or filter clicks.
     */
    public function runSearch(): void
    {
        $this->logSearch();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'term' => ['nullable', 'string', 'max:120'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in(SearchIndex::keys())],
            'status' => ['nullable', 'string', 'max:30'],
            'owner' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    protected function isValid(): bool
    {
        return Validator::make($this->all(), $this->rules())->passes();
    }

    /**
     * @return array{modules: list<string>, status: ?string, owner: ?string, from: ?string, to: ?string}
     */
    protected function filters(): array
    {
        return [
            'modules' => $this->modules,
            'status' => $this->status ?: null,
            'owner' => $this->owner ?: null,
            'from' => $this->from ?: null,
            'to' => $this->to ?: null,
        ];
    }

    /**
     * @return Collection<int, array{entity: object, rows: Collection, count: int}>
     */
    protected function results(): Collection
    {
        $term = trim($this->term);

        if ($term === '' || ! $this->isValid()) {
            return collect();
        }

        return app(SearchService::class)->search(auth()->user(), $term, $this->filters());
    }

    protected function logSearch(): void
    {
        $this->validate();

        $term = trim($this->term);

        if ($term === '') {
            return;
        }

        app(SearchLogger::class)->record(auth()->user(), $term, $this->results(), $this->filters());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $groups = $this->results();
        $search = app(SearchService::class);

        $statusValues = [];

        foreach (SearchIndex::forUser($user) as $entity) {
            if ($entity->statusColumn !== null) {
                $statusValues[$entity->key] = $search->statusValuesFor($user, $entity);
            }
        }

        return [
            'term' => trim($this->term),
            'groups' => $groups,
            'total' => $groups->sum('count'),
            'entities' => SearchIndex::forUser($user),
            'statusValues' => collect($statusValues)->flatten()->unique()->sort()->values()->all(),
            'owners' => User::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all(),
        ];
    }
}
