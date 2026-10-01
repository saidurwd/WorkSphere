<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\Tag;
use App\Models\User;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Tasks\Models\Task;
use Modules\Todos\Jobs\SendTodoNotificationJob;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Writing through the To-Do screens.
 *
 * The bulk cases are the important ones: authorisation is decided per item
 * server-side, because holding a permission over one To-Do must never grant it
 * over the rest of the selection. A client-side check is a convenience, and this
 * is the proof the server does not depend on it.
 */
class TodoUiWriteTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Quick capture ------------------------------------------------------

    public function test_quick_capture_creates_a_todo_and_returns_to_the_list(): void
    {
        Queue::fake();

        $user = $this->userWithPermissions(['todos.create']);

        $response = $this->actingAs($user)->post(route('todos.store'), [
            'title' => 'Captured in one line',
            'quick_capture' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('todos', ['title' => 'Captured in one line']);

        Queue::assertPushed(SendTodoNotificationJob::class, 0);
    }

    public function test_quick_capture_stays_on_the_list_page(): void
    {
        $user = $this->userWithPermissions(['todos.create']);

        $response = $this->from(route('todos.index'))
            ->actingAs($user)
            ->post(route('todos.store'), ['title' => 'Stay here', 'quick_capture' => '1']);

        $response->assertRedirect(route('todos.index'));
    }

    public function test_capture_requires_the_create_permission(): void
    {
        $this->actingAs($this->plainUser())
            ->post(route('todos.store'), ['title' => 'Should not exist'])
            ->assertForbidden();

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_capture_rejects_a_whitespace_only_title(): void
    {
        $user = $this->userWithPermissions(['todos.create']);

        $this->actingAs($user)
            ->post(route('todos.store'), ['title' => '   '])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_the_create_form_redirects_to_the_detail_page(): void
    {
        $user = $this->userWithPermissions(['todos.create']);

        $this->actingAs($user)
            ->post(route('todos.store'), ['title' => 'Full form'])
            ->assertRedirect(route('todos.show', Todo::query()->firstOrFail()));
    }

    public function test_creating_for_another_user_requires_create_for_others(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todos.store'), ['title' => 'Delegated', 'assignee_id' => $other->id])
            ->assertForbidden();

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_creating_for_another_user_is_allowed_with_the_permission(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.create_for_others']);
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todos.store'), ['title' => 'Delegated', 'assignee_id' => $other->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('todos', ['title' => 'Delegated', 'assignee_id' => $other->id]);
    }

    // ---- Update -------------------------------------------------------------

    public function test_the_owner_can_edit_their_todo(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->put(route('todos.update', $todo), [
                'title' => 'Edited',
                'priority' => Priority::High->value,
                'visibility' => Visibility::Personal->value,
            ])
            ->assertRedirect(route('todos.show', $todo));

        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => 'Edited']);
    }

    public function test_a_stranger_gets_404_and_cannot_edit(): void
    {
        $todo = Todo::factory()->create();

        $this->actingAs($this->plainUser())
            ->put(route('todos.update', $todo), ['title' => 'Hijacked'])
            ->assertNotFound();

        $this->assertDatabaseMissing('todos', ['title' => 'Hijacked']);
    }

    public function test_an_update_cannot_smuggle_a_status_change(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create(['status' => WorkItemStatus::Inbox]);

        $this->actingAs($user)
            ->put(route('todos.update', $todo), [
                'title' => 'Renamed',
                'priority' => Priority::Medium->value,
                'visibility' => Visibility::Personal->value,
                'status' => WorkItemStatus::Completed->value,
            ])
            ->assertRedirect();

        $this->assertSame(
            WorkItemStatus::Inbox,
            $todo->fresh()->status,
            'A form update must not bypass the transition graph.',
        );
    }

    // ---- Lifecycle ----------------------------------------------------------

    public function test_the_owner_can_complete_and_reopen(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create(['status' => WorkItemStatus::InProgress]);

        $this->actingAs($user)->post(route('todos.complete', $todo))->assertRedirect();
        $this->assertSame(WorkItemStatus::Completed, $todo->fresh()->status);

        $this->actingAs($user)->post(route('todos.reopen', $todo))->assertRedirect();
        $this->assertSame(WorkItemStatus::InProgress, $todo->fresh()->status);
    }

    public function test_a_stranger_cannot_complete_someone_elses_todo(): void
    {
        $todo = Todo::factory()->create(['status' => WorkItemStatus::InProgress]);

        $this->actingAs($this->plainUser())->post(route('todos.complete', $todo))->assertNotFound();

        $this->assertSame(WorkItemStatus::InProgress, $todo->fresh()->status);
    }

    public function test_assigning_to_a_nonexistent_user_is_refused(): void
    {
        // Reassigning needs `todos.assign` (§4.1).
        $user = $this->userWithPermissions(['todos.assign']);
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->post(route('todos.assign', $todo), ['assignee_id' => 999999])
            ->assertStatus(422);

        $this->assertNull($todo->fresh()->assignee_id);
    }

    public function test_a_stranger_cannot_reassign_someone_elses_todo(): void
    {
        $todo = Todo::factory()->create();
        $newAssignee = User::factory()->create();

        $this->actingAs($this->plainUser())
            ->post(route('todos.assign', $todo), ['assignee_id' => $newAssignee->id])
            ->assertNotFound();

        $this->assertNull($todo->fresh()->assignee_id);
    }

    // ---- Checklist / watchers / links --------------------------------------

    public function test_a_checklist_item_can_be_added_and_toggled(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->post(route('todos.checklist.store', $todo), ['title' => 'First step'])
            ->assertRedirect();

        $item = $todo->checklistItems()->firstOrFail();

        $this->actingAs($user)
            ->put(route('todos.checklist.update', [$todo, $item]), [
                'title' => 'First step',
                'is_completed' => 1,
            ])
            ->assertRedirect();

        $this->assertTrue($item->fresh()->is_completed);
        $this->assertNotNull($item->fresh()->completed_at);
        $this->assertSame($user->id, $item->fresh()->completed_by);
    }

    public function test_a_checklist_item_from_another_todo_is_404(): void
    {
        $user = $this->plainUser();
        $mine = Todo::factory()->createdBy($user)->create();
        $theirs = Todo::factory()->create();

        $item = $mine->checklistItems()->create(['title' => 'Mine']);

        $this->actingAs($user)
            ->delete(route('todos.checklist.destroy', [$theirs, $item]))
            ->assertNotFound();

        $this->assertDatabaseHas('todo_checklist_items', ['id' => $item->id]);
    }

    public function test_watching_the_same_todo_twice_is_idempotent(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();
        $watcher = User::factory()->create();

        $this->actingAs($user)->post(route('todos.watchers.store', $todo), ['user_id' => $watcher->id]);
        $this->actingAs($user)->post(route('todos.watchers.store', $todo), ['user_id' => $watcher->id]);

        $this->assertSame(1, $todo->watchers()->count());
    }

    public function test_a_forged_linkable_type_is_refused(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        // `linkable_type` is a hidden field, so it is untrusted input.
        $this->actingAs($user)
            ->post(route('todos.links.store', $todo), [
                'linkable_type' => 'App\\Models\\User',
                'linkable_id' => 1,
            ])
            ->assertSessionHasErrors('linkable_type');

        $this->assertDatabaseCount('todo_links', 0);
    }

    public function test_a_permitted_link_type_can_be_attached(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();
        $task = TaskFactory::new()->create();

        $this->actingAs($user)
            ->post(route('todos.links.store', $todo), [
                'linkable_type' => 'task',
                'linkable_id' => $task->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('todo_links', [
            'todo_id' => $todo->id,
            'linkable_type' => Task::class,
            'linkable_id' => $task->id,
        ]);
    }

    public function test_a_link_to_a_missing_record_is_a_validation_failure(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->post(route('todos.links.store', $todo), [
                'linkable_type' => 'task',
                'linkable_id' => 999999,
            ])
            ->assertSessionHasErrors('linkable_id');

        $this->assertDatabaseCount('todo_links', 0);
    }

    public function test_a_comment_is_stored_on_the_shared_table(): void
    {
        $user = $this->userWithPermissions(['todos.comment']);
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->post(route('todos.comments.store', $todo), ['body' => 'Looks good'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Todo::class,
            'commentable_id' => $todo->id,
            'body' => 'Looks good',
        ]);
    }

    public function test_commenting_without_the_permission_is_refused(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->post(route('todos.comments.store', $todo), ['body' => 'Not allowed'])
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    // ---- Bulk actions -------------------------------------------------------

    public function test_bulk_complete_applies_to_all_selected_items(): void
    {
        $user = $this->plainUser();

        $mine = Todo::factory()->count(3)->createdBy($user)->create([
            'status' => WorkItemStatus::InProgress,
        ]);

        $this->actingAs($user)
            ->post(route('todos.bulk'), [
                'action' => 'complete',
                'ids' => $mine->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($mine as $todo) {
            $this->assertSame(WorkItemStatus::Completed, $todo->fresh()->status);
        }
    }

    public function test_bulk_skips_items_the_actor_may_not_act_on(): void
    {
        $user = $this->plainUser();

        $mine = Todo::factory()->count(2)->createdBy($user)->create([
            'status' => WorkItemStatus::InProgress,
        ]);
        $theirs = Todo::factory()->create(['status' => WorkItemStatus::InProgress]);

        $response = $this->actingAs($user)->post(route('todos.bulk'), [
            'action' => 'complete',
            'ids' => [...$mine->pluck('id')->all(), $theirs->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // The unauthorised item is untouched — not silently changed, not errored.
        $this->assertSame(
            WorkItemStatus::InProgress,
            $theirs->fresh()->status,
            'A bulk action must not touch an item the actor may not act on.',
        );

        foreach ($mine as $todo) {
            $this->assertSame(WorkItemStatus::Completed, $todo->fresh()->status);
        }
    }

    public function test_bulk_reports_how_many_were_skipped(): void
    {
        $user = $this->plainUser();
        $mine = Todo::factory()->createdBy($user)->create(['status' => WorkItemStatus::InProgress]);
        $theirs = Todo::factory()->create(['status' => WorkItemStatus::InProgress]);

        $response = $this->actingAs($user)->post(route('todos.bulk'), [
            'action' => 'complete',
            'ids' => [$mine->id, $theirs->id],
        ]);

        $response->assertSessionHas('success');
        $this->assertStringContainsString('skipped', (string) session('success'));
    }

    public function test_bulk_archive_is_authorised_per_item_too(): void
    {
        $user = $this->plainUser();
        $mine = Todo::factory()->createdBy($user)->create(['status' => WorkItemStatus::InProgress]);
        $theirs = Todo::factory()->create(['status' => WorkItemStatus::InProgress]);

        $this->actingAs($user)->post(route('todos.bulk'), [
            'action' => 'archive',
            'ids' => [$mine->id, $theirs->id],
        ]);

        $this->assertSame(WorkItemStatus::Archived, $mine->fresh()->status);
        $this->assertSame(WorkItemStatus::InProgress, $theirs->fresh()->status);
    }

    public function test_bulk_reassign_only_touches_permitted_items(): void
    {
        // Reassigning needs `todos.assign` (§4.1), not just ownership.
        $user = $this->userWithPermissions(['todos.assign']);
        $newAssignee = User::factory()->create();

        $mine = Todo::factory()->createdBy($user)->create();
        $theirs = Todo::factory()->create();

        $this->actingAs($user)->post(route('todos.bulk'), [
            'action' => 'reassign',
            'ids' => [$mine->id, $theirs->id],
            'assignee_id' => $newAssignee->id,
        ]);

        $this->assertSame($newAssignee->id, $mine->fresh()->assignee_id);
        $this->assertNull($theirs->fresh()->assignee_id);
    }

    public function test_bulk_tag_applies_the_tag_to_permitted_items(): void
    {
        $user = $this->plainUser();
        $mine = Todo::factory()->createdBy($user)->create();
        $theirs = Todo::factory()->create();

        $this->actingAs($user)->post(route('todos.bulk'), [
            'action' => 'tag',
            'ids' => [$mine->id, $theirs->id],
            'tag' => 'Budget',
        ]);

        $tag = Tag::query()->where('name', 'Budget')->firstOrFail();

        $this->assertTrue($mine->tags()->whereKey($tag->id)->exists());
        $this->assertFalse($theirs->tags()->whereKey($tag->id)->exists());
    }

    public function test_bulk_rejects_an_empty_selection(): void
    {
        $user = $this->plainUser();

        $this->actingAs($user)
            ->post(route('todos.bulk'), ['action' => 'complete', 'ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_bulk_rejects_an_unknown_action(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->post(route('todos.bulk'), ['action' => 'detonate', 'ids' => [$todo->id]])
            ->assertSessionHasErrors('action');
    }

    public function test_bulk_requires_authentication(): void
    {
        $this->post(route('todos.bulk'), ['action' => 'complete', 'ids' => [1]])
            ->assertRedirect(route('login'));
    }
}
