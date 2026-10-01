<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The navbar search box and its type-ahead endpoint.
 *
 * The endpoint is the security boundary this page depends on: the box renders
 * whatever it returns, in every page's chrome. Two things are therefore pinned
 * here — that a user without a module's permission gets nothing from it (the same
 * query-layer filtering as the full page), and that titles are returned as JSON
 * data rather than as markup for the client to inject.
 */
class NavbarSearchBoxTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Markup --------------------------------------------------------------

    public function test_every_authenticated_page_carries_the_search_box(): void
    {
        $response = $this->actingAs($this->plainUser())->get(route('dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-search-box', false);
        $response->assertSee('id="navbar-search"', false);
    }

    public function test_the_box_is_a_real_form_so_it_works_without_javascript(): void
    {
        $response = $this->actingAs($this->plainUser())->get(route('dashboard.index'));

        $response->assertSee('action="'.route('search').'"', false);
        $response->assertSee('role="search"', false);
    }

    public function test_the_box_is_labelled_and_announced(): void
    {
        $response = $this->actingAs($this->plainUser())->get(route('dashboard.index'));

        // A visually-hidden label, an aria-live status region for the result
        // count, and combobox wiring for the dropdown.
        $response->assertSee('<label for="navbar-search" class="visually-hidden">Search</label>', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('role="combobox"', false);
        $response->assertSee('aria-controls="navbar-search-results"', false);
    }

    public function test_the_box_carries_its_endpoint_as_a_data_attribute(): void
    {
        // The script is a plain Vite asset, so a Blade expression inside it would
        // reach the browser literally; the URL has to arrive from the view.
        $response = $this->actingAs($this->plainUser())->get(route('dashboard.index'));

        $response->assertSee('data-search-endpoint="'.route('search.suggest').'"', false);
    }

    public function test_the_endpoint_route_is_registered_on_the_layout(): void
    {
        $this->assertTrue(
            str_contains(
                (string) file_get_contents(base_path('resources/views/layouts/app.blade.php')),
                'resources/js/search-box.js',
            ),
            'The script is built but not loaded.',
        );
    }

    /**
     * The box is centred by sitting in its own flex column between the left and
     * right clusters — not by being pushed right with `ms-auto`, which is where it
     * started. Asserting the rendered order is the only way to catch a regression
     * here; the classes look fine in a diff either way.
     */
    public function test_the_box_is_centred_between_the_left_and_right_clusters(): void
    {
        $html = $this->actingAs($this->plainUser())
            ->get(route('dashboard.index'))
            ->content();

        $left = strpos($html, 'navbar-nav flex-shrink-0');
        $centre = strpos($html, 'navbar-search flex-grow-1');
        $right = strpos($html, 'navbar-nav ms-auto');

        $this->assertNotFalse($left, 'The left cluster is missing.');
        $this->assertNotFalse($centre, 'The centred search column is missing.');
        $this->assertNotFalse($right, 'The right cluster is missing.');

        $this->assertLessThan($centre, $left, 'The left cluster must come before the search box.');
        $this->assertLessThan($right, $centre, 'The search box must come before the right cluster.');
    }

    public function test_the_search_box_has_a_narrow_screen_fallback(): void
    {
        // Below `md` the centred column is hidden, so search would otherwise be
        // unreachable on a phone.
        $html = $this->actingAs($this->plainUser())
            ->get(route('dashboard.index'))
            ->content();

        $this->assertStringContainsString('d-md-none', $html);
        $this->assertStringContainsString(route('search'), $html);
    }

    public function test_an_anonymous_visitor_gets_no_search_box(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    }

    // ---- Endpoint ------------------------------------------------------------

    /**
     * An unauthenticated hit redirects to the login screen rather than returning
     * 401. This route lives in the `web` group, and that is what the `auth`
     * middleware does for a non-`api/*` request — which is the correct and safe
     * outcome. The box is only rendered for a signed-in user in any case.
     */
    public function test_the_suggest_endpoint_requires_authentication(): void
    {
        $this->getJson(route('search.suggest', ['q' => 'quokka']))
            ->assertRedirect(route('login'));
    }

    public function test_the_suggest_endpoint_answers_with_json(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        Todo::factory()->createdBy($user)->create(['title' => 'Quokka typed']);

        // The client parses this with `response.json()`, so the content type is
        // what makes the box work at all.
        $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => 'quokka']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_the_suggest_endpoint_returns_grouped_results(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->count(2)->createdBy($user)->create(['title' => 'Quokka notes']);

        $response = $this->actingAs($user)->getJson(route('search.suggest', ['q' => 'quokka']));

        $response->assertOk();
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('groups.0.key', 'todo');
        $response->assertJsonPath('groups.0.results.0.title', 'Quokka notes');
        $response->assertJsonPath('seeAllUrl', route('search', ['q' => 'quokka']));
    }

    public function test_the_suggest_endpoint_links_to_the_record(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        $todo = Todo::factory()->createdBy($user)->create(['title' => 'Quokka linked']);

        $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => 'quokka']))
            ->assertOk()
            ->assertJsonPath('groups.0.results.0.url', route('todos.show', $todo));
    }

    public function test_the_suggest_endpoint_hides_modules_the_user_cannot_see(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        TaskFactory::new()->count(3)->create([
            'title' => 'Quokka task',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => 'quokka']))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'groups');
    }

    public function test_the_suggest_endpoint_applies_the_same_ownership_rule(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->create(['title' => 'Quokka theirs']);

        $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => 'quokka']))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_the_suggest_endpoint_returns_a_title_as_data_not_as_markup(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->createdBy($user)->create([
            'title' => 'Quokka <script>alert(1)</script>',
        ]);

        $response = $this->actingAs($user)->getJson(route('search.suggest', ['q' => 'quokka']));

        $response->assertOk();

        // The title is data. The client inserts it with textContent, so what
        // matters is that it round-trips verbatim and is not pre-escaped or
        // wrapped in anything the browser would execute.
        $this->assertSame(
            'Quokka <script>alert(1)</script>',
            $response->json('groups.0.results.0.title'),
        );
    }

    public function test_the_suggest_endpoint_rejects_an_unknown_module(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => 'quokka', 'modules' => ['nope']]))
            ->assertRedirect()
            ->assertSessionHasErrors('modules.0');
    }

    public function test_the_suggest_endpoint_requires_a_term(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        $this->actingAs($user)
            ->getJson(route('search.suggest'))
            ->assertRedirect()
            ->assertSessionHasErrors('q');
    }

    public function test_the_suggest_endpoint_survives_an_injection_shaped_term(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        Todo::factory()->createdBy($user)->create(['title' => 'Numbat']);

        $this->actingAs($user)
            ->getJson(route('search.suggest', ['q' => "'; DROP TABLE todos; --"]))
            ->assertOk();

        $this->assertTrue(Schema::hasTable('todos'));
    }

    public function test_the_suggest_endpoint_caps_results_per_group(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->count(9)->createdBy($user)->create(['title' => 'Quokka many']);

        $response = $this->actingAs($user)->getJson(route('search.suggest', ['q' => 'quokka']));

        $response->assertOk();
        $this->assertLessThanOrEqual(
            4,
            count($response->json('groups.0.results')),
            'The dropdown is a shortcut, not the full page.',
        );
    }
}
