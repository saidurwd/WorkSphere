<?php

namespace Tests\Feature;

use App\Enums\LinkType;
use App\Models\User;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoLink;
use Modules\Todos\Models\TodoWatcher;
use Modules\Todos\Services\TodoLinkService;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Cross-module navigation — TODO-MODULE-SPECIFICATION §7.2.
 *
 * Two properties matter and both are security properties, not convenience ones:
 *
 * 1. Navigation works in **both** directions — a To-Do lists its Task and that
 *    Task lists the To-Do — through the same link row rather than a second
 *    nullable FK column per module.
 * 2. A link whose target the viewer cannot open must render **nothing**, not even
 *    a hidden element containing the target's title. The list itself leaks.
 */
class CrossModuleNavigationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Forward: create a To-Do from another module -----------------------

    public function test_a_todo_can_be_created_from_a_task_and_navigated_both_ways(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.create_for_others']);
        $task = TaskFactory::new()->create(['title' => 'Ship the release']);

        $this->actingAs($user)
            ->post(route('todos.links.from.task', $task))
            ->assertRedirect();

        $todo = Todo::query()->withoutGlobalScopes()->firstOrFail();

        $this->assertSame('Ship the release', $todo->title);

        // Forward: the To-Do points at the Task.
        $link = $todo->links()->firstOrFail();
        $this->assertSame(Task::class, $link->linkable_type);
        $this->assertSame($task->id, $link->linkable_id);

        // Reverse: the Task resolves back to the To-Do.
        $reverse = app(TodoLinkService::class)->resolveReverse(
            $this->userWithPermissions(['todos.view_all']),
            'task',
            $task->id,
        );

        $this->assertTrue($reverse->contains('id', $todo->id));
    }

    public function test_a_todo_can_be_created_from_a_meeting_action_item(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.create_for_others']);
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $actionItem = MeetingActionItem::query()->create([
            'meeting_id' => $meeting->id,
            'action_no' => 1,
            'title' => 'Draft the agenda',
            'priority' => 'urgent',
            'status' => 'open',
            'assigned_to' => $organizer->id,
        ]);

        $this->actingAs($user)
            ->post(route('todos.links.from.action-item', $actionItem))
            ->assertRedirect();

        $todo = Todo::query()->withoutGlobalScopes()->firstOrFail();

        $this->assertSame('Draft the agenda', $todo->title);
        // `urgent` is not a Priority case; it maps to `high` rather than being dropped.
        $this->assertSame('high', $todo->priority->value);
        $this->assertSame($organizer->id, $todo->assignee_id);

        $this->assertDatabaseHas('todo_links', [
            'todo_id' => $todo->id,
            'linkable_type' => MeetingActionItem::class,
            'linkable_id' => $actionItem->id,
        ]);
    }

    public function test_a_todo_can_be_created_from_an_obligation(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.create_for_others']);
        $obligation = ObligationFactory::new()->create(['title' => 'Renew the vendor contract']);

        $this->actingAs($user)
            ->post(route('todos.links.from.obligation', $obligation))
            ->assertRedirect();

        $todo = Todo::query()->withoutGlobalScopes()->firstOrFail();

        $this->assertSame('Renew the vendor contract', $todo->title);
        $this->assertDatabaseHas('todo_links', [
            'todo_id' => $todo->id,
            'linkable_type' => Obligation::class,
            'linkable_id' => $obligation->id,
        ]);
    }

    public function test_creating_a_todo_for_someone_else_requires_create_for_others(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $task = TaskFactory::new()->create();

        $this->actingAs($user)
            ->post(route('todos.links.from.task', $task))
            ->assertForbidden();

        $this->assertSame(0, Todo::query()->withoutGlobalScopes()->count());
    }

    public function test_an_anonymous_visitor_cannot_create_a_cross_module_todo(): void
    {
        $task = TaskFactory::new()->create();

        $this->post(route('todos.links.from.task', $task))->assertRedirect(route('login'));
    }

    // ---- Reverse navigation -------------------------------------------------

    public function test_the_reverse_endpoint_lists_the_linked_todos(): void
    {
        $user = $this->userWithPermissions(['todos.create_for_others', 'todos.view_all']);
        $task = TaskFactory::new()->create();

        $this->actingAs($user)->post(route('todos.links.from.task', $task));

        $response = $this->actingAs($user)
            ->getJson(route('todos.links.reverse', ['morphKey' => 'task', 'id' => $task->id]));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_reverse_endpoint_returns_nothing_without_the_view_permission(): void
    {
        $user = $this->userWithPermissions(['todos.create_for_others', 'todos.view_all']);
        $task = TaskFactory::new()->create();

        $this->actingAs($user)->post(route('todos.links.from.task', $task));

        // A user who cannot view tasks learns nothing about the links either.
        // They hold todos.create_for_others so the gate passes and the *filter*
        // is what is under test, not the authorisation.
        $stranger = $this->userWithPermissions(['todos.create_for_others']);

        $this->actingAs($stranger)
            ->getJson(route('todos.links.reverse', ['morphKey' => 'task', 'id' => $task->id]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ---- No title leak ------------------------------------------------------

    public function test_a_link_to_a_task_the_viewer_cannot_see_renders_nothing(): void
    {
        $owner = $this->plainUser();
        $viewer = $this->plainUser();

        $todo = Todo::factory()->createdBy($owner)->create(['title' => 'Mine']);
        $task = TaskFactory::new()->create(['title' => 'Confidential task title']);

        DB::table('todo_links')->insert([
            'todo_id' => $todo->id,
            'linkable_type' => Task::class,
            'linkable_id' => $task->id,
            'link_type' => LinkType::Related->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The viewer can see the To-Do — as a watcher — but holds no task.view
        // permission, so the Task is the thing they must not learn about.
        TodoWatcher::query()->create([
            'todo_id' => $todo->id,
            'user_id' => $viewer->id,
        ]);

        $this->actingAs($viewer);

        $response = $this->get(route('todos.show', $todo));

        $response->assertOk();
        $response->assertDontSee('Confidential task title');
    }

    public function test_a_link_to_an_obligation_the_viewer_cannot_see_renders_nothing(): void
    {
        $owner = $this->plainUser();
        $viewer = $this->plainUser();

        $todo = Todo::factory()->createdBy($owner)->create();
        $obligation = ObligationFactory::new()->create(['title' => 'Secret obligation title']);

        DB::table('todo_links')->insert([
            'todo_id' => $todo->id,
            'linkable_type' => Obligation::class,
            'linkable_id' => $obligation->id,
            'link_type' => LinkType::Related->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TodoWatcher::query()->create([
            'todo_id' => $todo->id,
            'user_id' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('todos.show', $todo))
            ->assertOk()
            ->assertDontSee('Secret obligation title');
    }

    public function test_visible_targets_only_contain_permitted_records(): void
    {
        $owner = $this->userWithPermissions(['todos.view_all', 'task.view']);
        $todo = Todo::factory()->createdBy($owner)->create();

        $task = TaskFactory::new()->create();
        DB::table('todo_links')->insert([
            'todo_id' => $todo->id,
            'linkable_type' => Task::class,
            'linkable_id' => $task->id,
            'link_type' => LinkType::Related->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $visible = app(TodoLinkService::class)->visibleTargets($owner, $todo->fresh());

        $this->assertCount(1, $visible);
    }

    // ---- No duplicated concept ---------------------------------------------

    public function test_creating_the_same_link_twice_does_not_duplicate_it(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->createdBy($user)->create();
        $task = TaskFactory::new()->create();

        $service = app(TodoLinkService::class);

        $service->attach($user, $todo, 'task', $task->id);
        $service->attach($user, $todo, 'task', $task->id);

        $this->assertSame(
            1,
            TodoLink::query()->where('todo_id', $todo->id)->count(),
            'The composite unique index makes a duplicate link impossible.',
        );
    }

    public function test_no_new_nullable_foreign_key_column_was_added_to_a_module_table(): void
    {
        // The whole point of todo_links is that Task, Meeting and Obligation keep
        // their schemas. A `todo_id` appearing on any of them means the linkage was
        // done the way §7.2 explicitly rules out.
        foreach (['tasks', 'meetings', 'obligations', 'meeting_action_items'] as $table) {
            $this->assertFalse(
                Schema::hasColumn($table, 'todo_id'),
                "{$table}.todo_id exists — linkage should go through todo_links.",
            );
        }
    }
}
