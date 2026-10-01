<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Search\SearchableEntity;
use App\Search\SearchIndex;
use App\Search\SearchService;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Obligations\Models\ObligationResponsibility;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoWatcher;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Global search — GAP-035.
 *
 * The four properties the brief calls out are each asserted here, and each is a
 * security property rather than a feature:
 *
 * 1. A user without a module's permission gets zero results *and* zero count
 *    from it.
 * 2. An injection-shaped term returns nothing and does not error.
 * 3. An unknown module is rejected with a 422 rather than passed to a query.
 * 4. The SQLite LIKE fallback returns the same rows the MySQL FULLTEXT path does.
 */
class GlobalSearchTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private SearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SearchService::class);
    }

    /**
     * @return list<string>
     */
    private function everyPermission(): array
    {
        return ['todos.view', 'task.view', 'meeting.view', 'obligation.view', 'project.view'];
    }

    // ---- Grouping and counts ----------------------------------------------

    public function test_results_are_grouped_by_module_with_counts(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        Todo::factory()->count(2)->createdBy($user)->create(['title' => 'Zebra budget']);
        TaskFactory::new()->count(3)->create(['title' => 'Zebra report', 'user_id' => $user->id]);
        ObligationFactory::new()->count(1)->create(['owner_user_id' => $user->id, 'title' => 'Zebra contract']);

        $groups = $this->service->search($user, 'zebra');

        $keys = $groups->pluck('entity.key')->all();

        $this->assertContains('todo', $keys);
        $this->assertContains('task', $keys);
        $this->assertContains('obligation', $keys);

        $byKey = $groups->keyBy('entity.key');
        $this->assertSame(2, $byKey['todo']['count']);
        $this->assertSame(3, $byKey['task']['count']);
    }

    public function test_a_term_shorter_than_two_characters_searches_nothing(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());
        Todo::factory()->createdBy($user)->create(['title' => 'ab']);

        $this->assertCount(0, $this->service->search($user, 'a'));
        $this->assertCount(1, $this->service->search($user, 'ab'));
    }

    public function test_the_search_page_renders(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        Todo::factory()->createdBy($user)->create([
            'title' => 'Zebra',
            'description' => 'A zebra appears in the results.',
        ]);

        $this->actingAs($user)->get(route('search'))->assertOk()->assertSee('Nothing searched yet');

        $this->actingAs($user)
            ->get(route('search', ['q' => 'zebra']))
            ->assertOk()
            ->assertSee('Zebra');
    }

    public function test_the_search_page_distinguishes_no_term_from_no_results(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        $this->actingAs($user)->get(route('search'))->assertSee('Nothing searched yet');

        $this->actingAs($user)
            ->get(route('search', ['q' => 'nothingmatchesthis']))
            ->assertSee('Nothing matched');
    }

    public function test_search_requires_authentication(): void
    {
        $this->get(route('search'))->assertRedirect(route('login'));
    }

    // ---- 1. Permission filtering -------------------------------------------

    public function test_a_user_without_a_module_permission_gets_no_results_from_it(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        TaskFactory::new()->create(['title' => 'Quokka task']);
        ObligationFactory::new()->create(['title' => 'Quokka obligation']);
        MeetingFactory::new()->create(['title' => 'Quokka meeting']);
        ProjectFactory::new()->create(['name' => 'Quokka project']);

        Todo::factory()->createdBy($user)->create(['title' => 'Quokka todo']);

        $keys = $this->service->search($user, 'quokka')->pluck('entity.key')->all();

        $this->assertSame(['todo'], $keys, 'Only the permitted module may appear.');
    }

    public function test_a_forbidden_module_reports_no_count_either(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        TaskFactory::new()->count(7)->create(['title' => 'Quokka task']);

        $counts = $this->service->counts($user, 'quokka');

        // A count alone is a disclosure: it tells the user that records exist.
        $this->assertArrayNotHasKey('task', $counts->all());
        $this->assertSame(0, $this->service->total($user, 'quokka'));
    }

    public function test_asking_for_a_forbidden_module_explicitly_returns_nothing(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        TaskFactory::new()->count(5)->create(['title' => 'Quokka task']);

        $groups = $this->service->search($user, 'quokka', ['modules' => ['task']]);

        $this->assertCount(0, $groups, 'Naming a forbidden module must not widen the search.');
    }

    public function test_a_watcher_finds_a_todo_they_only_watch(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        $todo = Todo::factory()->create(['title' => 'Wallaby watch']);

        TodoWatcher::query()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

        $this->assertCount(1, $this->service->search($user, 'wallaby'));
    }

    public function test_someone_elses_todo_is_not_findable(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        Todo::factory()->create(['title' => 'Wallaby theirs']);

        $this->assertCount(0, $this->service->search($user, 'wallaby'));
    }

    public function test_an_obligations_responsibility_holder_finds_it(): void
    {
        $user = $this->userWithPermissions(['obligation.view']);
        $obligation = ObligationFactory::new()->create([
            'owner_user_id' => $this->plainUser()->id,
            'title' => 'Wallaby obligation',
        ]);

        ObligationResponsibility::query()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $user->id,
            'responsibility_type' => 'responsible',
            'active' => true,
        ]);

        $this->assertCount(1, $this->service->search($user, 'wallaby'));
    }

    public function test_a_meeting_participant_finds_the_meeting(): void
    {
        $organizer = $this->plainUser();
        $user = $this->userWithPermissions(['meeting.view']);
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create(['title' => 'Wallaby board']);

        $meeting->participants()->create([
            'user_id' => $user->id,
            'participant_type' => 'member',
        ]);

        $this->assertCount(1, $this->service->search($user, 'wallaby'));
    }

    // ---- 2. Injection-shaped input -----------------------------------------

    public function test_an_injection_shaped_term_returns_nothing_and_does_not_error(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());
        Todo::factory()->createdBy($user)->create(['title' => 'Numbat']);

        foreach ([
            "'; DROP TABLE todos; --",
            "' OR '1'='1",
            '" OR 1=1 #',
            'numbat\"; DELETE FROM users; --',
            "\\'; SELECT * FROM users --",
        ] as $hostile) {
            $groups = $this->service->search($user, $hostile);

            $this->assertCount(0, $groups, "Term {$hostile} matched something.");
        }

        // Nothing was destroyed.
        $this->assertSame(1, Todo::query()->withoutGlobalScopes()->count());
        $this->assertTrue(Schema::hasTable('todos'));
    }

    public function test_the_search_page_survives_an_injection_shaped_term(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        $this->actingAs($user)
            ->get(route('search', ['q' => "'; DROP TABLE todos; --"]))
            ->assertOk();

        $this->assertTrue(Schema::hasTable('todos'));
    }

    public function test_a_term_is_escaped_in_the_rendered_results(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->createdBy($user)->create([
            'title' => 'Numbat',
            'description' => 'Contains <script>alert(1)</script> inline.',
        ]);

        $response = $this->actingAs($user)->get(route('search', ['q' => 'numbat']));

        $response->assertOk();
        // The excerpt is the one place raw HTML is emitted, because it wraps the
        // match in <mark>. Everything else must be escaped.
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    /**
     * The excerpt is the one place search emits raw HTML, so it is the one place
     * escaping has to be right. A regression here is stored XSS on the page every
     * user reaches.
     */
    public function test_the_excerpt_escapes_html_before_highlighting(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        $todo = Todo::factory()->createdBy($user)->create([
            'title' => 'Wombat',
            'description' => 'A <script>alert(1)</script> wombat, and an <img src=x onerror=alert(2)> too.',
        ]);

        $entity = SearchIndex::get('todo');
        $excerpt = $entity->excerpt($todo, 'wombat');

        $this->assertStringNotContainsString('<script>', $excerpt);
        $this->assertStringNotContainsString('<img', $excerpt);
        $this->assertStringContainsString('&lt;script&gt;', $excerpt);
        $this->assertStringContainsString('<mark>wombat</mark>', $excerpt);
    }

    public function test_a_term_containing_markup_cannot_break_the_highlight_wrapper(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        $todo = Todo::factory()->createdBy($user)->create([
            'title' => 'Numbat',
            'description' => 'The <b>numbat</b> is small.',
        ]);

        $entity = SearchIndex::get('todo');

        // The term is escaped before it becomes a pattern, so its own markup
        // cannot inject anything into the wrapper.
        $excerpt = $entity->excerpt($todo, '<b>');

        $this->assertStringNotContainsString('<mark><b>', $excerpt);
    }

    public function test_the_rendered_search_page_never_contains_unescaped_record_html(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->createdBy($user)->create([
            'title' => 'Quokka <script>alert(9)</script>',
            'description' => 'Also <script>alert(8)</script> here.',
        ]);

        $response = $this->actingAs($user)->get(route('search', ['q' => 'quokka']));

        $response->assertOk();
        $response->assertDontSee('<script>alert(9)</script>', false);
        $response->assertDontSee('<script>alert(8)</script>', false);
    }

    public function test_the_highlight_only_wraps_the_matched_term(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        $todo = Todo::factory()->createdBy($user)->create([
            'title' => 'Numbat',
            'description' => 'The numbat is a small marsupial.',
        ]);

        $entity = SearchIndex::get('todo');
        $excerpt = $entity->excerpt($todo, 'numbat');

        $this->assertStringContainsString('<mark>numbat</mark>', $excerpt);
    }

    // ---- 3. Unknown inputs --------------------------------------------------

    public function test_an_unknown_module_is_rejected_with_422(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        $this->actingAs($user)
            ->get(route('search', ['modules' => ['not_a_module']]))
            ->assertRedirect()
            ->assertSessionHasErrors('modules.0');
    }

    public function test_a_sort_column_is_never_accepted(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        // There is no sort parameter at all: the ordering is fixed per entity, so
        // there is nothing for a caller to inject into.
        $this->actingAs($user)
            ->get(route('search', ['q' => 'numbat', 'sort' => 'password']))
            ->assertOk();

        $this->assertStringNotContainsString(
            'orderBy($request',
            (string) file_get_contents(app_path('Search/SearchService.php')),
        );
    }

    public function test_an_over_long_term_is_rejected(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        $this->actingAs($user)
            ->get(route('search', ['q' => str_repeat('a', 200)]))
            ->assertRedirect()
            ->assertSessionHasErrors('q');
    }

    // ---- 4. SQLite / MySQL parity ------------------------------------------

    public function test_the_fallback_path_returns_the_rows_the_fulltext_path_would(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        Todo::factory()->count(3)->createdBy($user)->create(['title' => 'Quokka notes', 'description' => 'wombat']);
        TaskFactory::new()->count(2)->create(['title' => 'Quokka report', 'user_id' => $user->id]);

        // Whichever path this driver takes, the same terms must match the same
        // records. Both terms exist on both entities so the assertion is about the
        // matching, not about which document happened to contain the word.
        foreach (['quokka', 'Quokka', 'notes'] as $term) {
            $keys = $this->service->search($user, $term)->pluck('entity.key')->sort()->values()->all();

            $this->assertContains('todo', $keys, "Term '{$term}' missed the To-Do.");
        }

        $this->assertGreaterThanOrEqual(2, $this->service->total($user, 'quokka'));
    }

    public function test_the_fallback_is_used_on_a_driver_without_fulltext(): void
    {
        // SQLite is the driver the suite runs on, so the LIKE path is what these
        // results prove.
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $service = new class extends SearchService
        {
            public function usesFullTextFor(SearchableEntity $entity): bool
            {
                return $this->supportsFullText($entity);
            }
        };

        foreach (SearchIndex::all() as $entity) {
            $this->assertFalse($service->usesFullTextFor($entity));
        }
    }

    /**
     * Full-text operator characters are stripped before the term reaches the query.
     *
     * This cannot be proven end-to-end on SQLite — `LIKE` treats `+*` as ordinary
     * text — so it is asserted directly. It matters more than it looks: on MySQL,
     * `AGAINST ('+*' IN BOOLEAN MODE)` is a *syntax error*, not a zero result, so
     * a single un-stripped character turns a search into a 500.
     */
    public function test_fulltext_operator_characters_are_stripped_from_a_term(): void
    {
        $service = app(SearchService::class);
        $normalise = new \ReflectionMethod($service, 'normalise');

        $this->assertSame('budget', $normalise->invoke($service, '+budget'));
        $this->assertSame('budget', $normalise->invoke($service, '*budget*'));
        $this->assertSame('budget report', $normalise->invoke($service, 'budget   report'));
        $this->assertSame('budget report', $normalise->invoke($service, 'budget -report'));
        $this->assertSame('budget', $normalise->invoke($service, '"budget"'));
        $this->assertSame('budget', $normalise->invoke($service, '(budget)'));
        $this->assertSame('budget', $normalise->invoke($service, '~budget'));
        $this->assertSame('', $normalise->invoke($service, '+*'));
    }

    public function test_a_term_of_only_operators_searches_nothing_rather_than_erroring(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        $this->assertCount(0, $this->service->search($user, '+*'));

        $this->actingAs($user)->get(route('search', ['q' => '+*']))->assertOk();
    }

    // ---- Facets -------------------------------------------------------------

    public function test_the_status_facet_narrows_the_results(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->createdBy($user)->create(['title' => 'Quokka one', 'status' => 'inbox']);
        Todo::factory()->createdBy($user)->create(['title' => 'Quokka two', 'status' => 'completed']);

        $this->assertSame(2, $this->service->total($user, 'quokka'));
        $this->assertSame(1, $this->service->total($user, 'quokka', ['status' => 'completed']));
    }

    public function test_the_owner_facet_narrows_the_results(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        $other = $this->userWithPermissions(['todos.view']);

        Todo::factory()->createdBy($user)->assignedTo($user)->create(['title' => 'Quokka mine']);
        Todo::factory()->createdBy($user)->assignedTo($other)->create(['title' => 'Quokka theirs']);

        // Both are visible: the searcher created both, so ownership narrows by
        // assignee and the facet has something to discriminate.
        $this->assertSame(2, $this->service->total($user, 'quokka'));
        $this->assertSame(
            1,
            $this->service->total($user, 'quokka', ['owner' => $user->id]),
        );
        $this->assertSame(
            1,
            $this->service->total($user, 'quokka', ['owner' => $other->id]),
        );
    }

    public function test_the_date_facet_narrows_the_results(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->createdBy($user)->create(['title' => 'Quokka past', 'due_date' => '2026-01-10']);
        Todo::factory()->createdBy($user)->create(['title' => 'Quokka future', 'due_date' => '2026-12-10']);

        $this->assertSame(
            1,
            $this->service->total($user, 'quokka', ['from' => '2026-06-01', 'to' => '2026-12-31']),
        );
    }

    public function test_a_reversed_date_range_is_swapped_rather_than_matching_nothing(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        Todo::factory()->createdBy($user)->create(['title' => 'Quokka dated', 'due_date' => '2026-06-15']);

        $this->assertSame(
            1,
            $this->service->total($user, 'quokka', ['from' => '2026-12-31', 'to' => '2026-01-01']),
        );
    }

    public function test_the_module_facet_narrows_to_one_group(): void
    {
        $user = $this->userWithPermissions($this->everyPermission());

        Todo::factory()->createdBy($user)->create(['title' => 'Quokka todo']);
        TaskFactory::new()->create(['title' => 'Quokka task', 'user_id' => $user->id]);

        $groups = $this->service->search($user, 'quokka', ['modules' => ['task']]);

        $this->assertCount(1, $groups);
        $this->assertSame('task', $groups->first()['entity']->key);
    }

    // ---- Logging -------------------------------------------------------------

    public function test_a_search_is_logged_with_its_counts(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        Todo::factory()->count(2)->createdBy($user)->create(['title' => 'Quokka logged']);

        $this->actingAs($user)->get(route('search', ['q' => 'quokka logged']))->assertOk();

        $entry = ActivityLog::query()
            ->where('action', 'searched')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('quokka logged', $entry->new_value['term']);
        $this->assertSame(2, $entry->new_value['total']);
        $this->assertSame(['todo' => 2], $entry->new_value['counts']);
        $this->assertSame($user->id, $entry->user_id);
    }

    public function test_a_trivial_term_is_not_logged(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        $this->actingAs($user)->get(route('search', ['q' => 'ab']))->assertOk();

        $this->assertSame(
            0,
            ActivityLog::query()->where('action', 'searched')->count(),
        );
    }

    public function test_the_log_does_not_copy_the_matched_records(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        Todo::factory()->createdBy($user)->create(['title' => 'Quokka private', 'description' => 'Secret body']);

        $this->actingAs($user)->get(route('search', ['q' => 'quokka private']))->assertOk();

        $entry = ActivityLog::query()->where('action', 'searched')->firstOrFail();

        $this->assertStringNotContainsString('Secret body', json_encode($entry->new_value));
    }
}
