<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;
use Modules\Todos\Models\TodoWatcher;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Every To-Do screen renders — TODO-MODULE-SPECIFICATION.md §13.
 *
 * A feature test that only asserts 200 misses the failures that matter here: a
 * Blade exception, a null dereference on an optional relation, a route that
 * points at a view that does not exist. Each case renders a populated record so
 * the whole branch is exercised, not just the empty one.
 */
class TodoScreenRenderTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_index_renders(): void
    {
        $user = $this->userWithPermissions(['todos.create']);

        Todo::factory()->count(2)->createdBy($user)->create();

        $this->actingAs($user)->get(route('todos.index'))->assertOk();
    }

    public function test_the_index_renders_with_no_todos_at_all(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('todos.index'))
            ->assertOk()
            ->assertSee('Nothing captured yet');
    }

    public function test_the_index_shows_filtered_empty_copy_when_a_filter_matches_nothing(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('todos.index', ['search' => 'zzzz-no-such-thing']))
            ->assertOk()
            ->assertSee('No To-Dos match these filters');
    }

    public function test_the_inbox_renders(): void
    {
        $user = $this->plainUser();

        Todo::factory()->count(2)->createdBy($user)->create([
            'status' => WorkItemStatus::Inbox,
        ]);

        $this->actingAs($user)->get(route('todos.inbox'))->assertOk();
    }

    public function test_create_renders(): void
    {
        $user = $this->userWithPermissions(['todos.create']);

        $this->actingAs($user)->get(route('todos.create'))->assertOk();
    }

    public function test_show_renders_with_every_panel_populated(): void
    {
        $user = $this->userWithPermissions(['todos.create']);

        $todo = Todo::factory()->createdBy($user)->create([
            'title' => 'Everything at once',
            'description' => 'A description with <script> in it.',
        ]);

        TodoChecklistItem::query()->create([
            'todo_id' => $todo->id,
            'title' => 'Step one',
            'is_completed' => true,
        ]);

        $watcher = User::factory()->create(['name' => 'Watcher Person']);
        TodoWatcher::query()->create(['todo_id' => $todo->id, 'user_id' => $watcher->id]);

        Comment::query()->create([
            'commentable_type' => Todo::class,
            'commentable_id' => $todo->id,
            'user_id' => $user->id,
            'body' => 'A comment body.',
        ]);

        $this->actingAs($user)
            ->get(route('todos.show', $todo))
            ->assertOk()
            ->assertSee('Everything at once')
            ->assertSee('Step one')
            ->assertSee('Watcher Person')
            ->assertSee('A comment body.');
    }

    public function test_a_user_description_is_escaped_not_rendered_as_html(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create([
            'description' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($user)->get(route('todos.show', $todo));

        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_edit_renders(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)->get(route('todos.edit', $todo))->assertOk();
    }

    public function test_the_calendar_renders_in_both_granularities(): void
    {
        $user = $this->plainUser();

        Todo::factory()->createdBy($user)->create(['due_date' => now()->format('Y-m-d')]);

        $this->actingAs($user)->get(route('todos.calendar', ['view' => 'month']))->assertOk();
        $this->actingAs($user)->get(route('todos.calendar', ['view' => 'week']))->assertOk();
    }

    public function test_the_calendar_renders_with_nothing_due(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('todos.calendar'))
            ->assertOk()
            ->assertSee('Nothing due in this period');
    }

    public function test_reports_render(): void
    {
        $user = $this->userWithPermissions(['todos.view_all']);

        Todo::factory()->count(3)->createdBy($user)->create(['status' => WorkItemStatus::Completed]);

        $this->actingAs($user)->get(route('todos.reports'))->assertOk();
    }

    public function test_the_notification_log_renders(): void
    {
        $user = $this->userWithPermissions(['todos.view_all']);

        DB::table('notification_logs')->insert([
            'subject_type' => Todo::class,
            'subject_id' => 1,
            'user_id' => $user->id,
            'channel' => 'database',
            'notification_type' => 'todo.assigned',
            'scheduled_at' => now(),
            'status' => 'SENT',
            'dedupe_key' => 'todo.assigned:1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('todos.notification-logs.index'))->assertOk();
    }

    // ---- Authorization ------------------------------------------------------

    public function test_a_user_who_cannot_view_a_todo_gets_404_not_an_empty_page(): void
    {
        $todo = Todo::factory()->create();

        // 404 rather than 403, and that is the better answer: TodoScope excludes
        // the record from route model binding entirely, so a 403 would confirm
        // that the id exists. §8.3 asks for "403/404, not a 200 with an empty page".
        $this->actingAs($this->plainUser())
            ->get(route('todos.show', $todo))
            ->assertNotFound();
    }

    public function test_a_user_who_cannot_edit_gets_404(): void
    {
        $todo = Todo::factory()->create();

        $this->actingAs($this->plainUser())
            ->get(route('todos.edit', $todo))
            ->assertNotFound();
    }

    public function test_a_user_who_cannot_act_on_a_todo_gets_404_on_the_mutation_route(): void
    {
        $todo = Todo::factory()->create();

        $this->actingAs($this->plainUser())
            ->post(route('todos.complete', $todo))
            ->assertNotFound();

        $this->assertSame(WorkItemStatus::Inbox, $todo->fresh()->status);
    }

    public function test_a_user_without_todos_create_cannot_reach_the_create_screen(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('todos.create'))
            ->assertForbidden();
    }

    public function test_reports_require_the_view_all_permission(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('todos.reports'))
            ->assertForbidden();
    }

    public function test_the_notification_log_requires_the_view_all_permission(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('todos.notification-logs.index'))
            ->assertForbidden();
    }

    public function test_an_anonymous_visitor_is_redirected_to_login(): void
    {
        $this->get(route('todos.index'))->assertRedirect(route('login'));
    }

    public function test_a_todo_the_viewer_cannot_see_is_absent_from_the_list(): void
    {
        $outsider = $this->plainUser();
        Todo::factory()->create(['title' => 'Confidential work']);

        $response = $this->actingAs($outsider)->get(route('todos.index'));

        $response->assertOk();
        $response->assertDontSee('Confidential work');
    }

    public function test_a_todo_the_viewer_can_see_appears_in_the_list(): void
    {
        $user = $this->plainUser();
        Todo::factory()->createdBy($user)->create(['title' => 'My own work']);

        $this->actingAs($user)
            ->get(route('todos.index'))
            ->assertOk()
            ->assertSee('My own work');
    }
}
