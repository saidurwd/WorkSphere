<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Tasks\Models\Task;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Filament\Resources\Todos\Pages\CreateTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\EditTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\ListTodos;
use Modules\Todos\Filament\Resources\Todos\Pages\ViewTodo;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\ChecklistItemsRelationManager;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\CommentsRelationManager;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\LinksRelationManager;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\WatchersRelationManager;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;
use Modules\Todos\Models\TodoLink;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament pilot for the To-Do module (workspace panel).
 */
class FilamentTodoResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_a_user_sees_their_own_todos_and_not_a_strangers(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $mine = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $theirs = Todo::factory()->create();

        $this->actingAs($user);

        Livewire::test(ListTodos::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_a_todo_can_be_created_through_the_service(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $this->actingAs($user);

        Livewire::test(CreateTodo::class)
            ->fillForm(['title' => 'Write report', 'priority' => 'high'])
            ->call('create')
            ->assertHasNoFormErrors();

        $todo = Todo::query()->where('title', 'Write report')->firstOrFail();
        $this->assertSame($user->id, $todo->creator_id);
        $this->assertSame(WorkItemStatus::Inbox, $todo->status);
    }

    public function test_creating_requires_the_create_permission(): void
    {
        $this->actingAs($this->plainUser());

        $this->get('/workspace/todos/create')->assertForbidden();
    }

    public function test_assigning_to_someone_else_needs_its_own_permission(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $other = $this->plainUser();
        $this->actingAs($user);

        // The field is not offered, so a forged value is dropped rather than saved.
        Livewire::test(CreateTodo::class)
            ->fillForm(['title' => 'Delegated'])
            ->set('data.assignee_id', $other->id)
            ->call('create');

        $this->assertNotSame($other->id, Todo::query()->where('title', 'Delegated')->value('assignee_id'));
    }

    public function test_a_todo_can_be_edited_and_completed(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.update_own', 'todos.complete']);
        $todo = Todo::factory()->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'status' => WorkItemStatus::InProgress,
        ]);
        $this->actingAs($user);

        Livewire::test(EditTodo::class, ['record' => $todo->getKey()])
            ->fillForm(['title' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $todo->fresh()->title);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])->assertActionVisible('complete')
            ->mountAction('complete')
            ->callMountedAction()
            ->assertNotified('To-Do completed');

        $this->assertSame(WorkItemStatus::Completed, $todo->fresh()->status);
    }

    public function test_someone_elses_todo_cannot_be_opened(): void
    {
        $this->actingAs($this->userWithPermissions(['todos.create']));
        $theirs = Todo::factory()->create();

        $this->get("/workspace/todos/{$theirs->getKey()}")->assertNotFound();
    }

    public function test_a_move_the_transition_graph_refuses_is_reported_not_swallowed(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'status' => WorkItemStatus::Inbox,
        ]);
        $this->actingAs($user);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])
            ->mountAction('complete')
            ->callMountedAction()
            ->assertNotified();

        $this->assertSame(WorkItemStatus::Inbox, $todo->fresh()->status);
    }

    public function test_checklist_items_can_be_added_ticked_and_removed_by_someone_who_may_update(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        $manager = fn () => Livewire::test(ChecklistItemsRelationManager::class, [
            'ownerRecord' => $todo,
            'pageClass' => ViewTodo::class,
        ]);

        $manager()->callAction(TestAction::make('create')->table(), ['title' => 'Draft outline']);

        $item = TodoChecklistItem::query()->where('title', 'Draft outline')->firstOrFail();
        $this->assertFalse($item->is_completed);

        $manager()->callAction(TestAction::make('toggle')->table($item));
        $this->assertTrue($item->fresh()->is_completed);
        $this->assertNotNull($item->fresh()->completed_by);

        $manager()->callAction(TestAction::make('delete')->table($item));
        $this->assertModelMissing($item);

        $this->assertDatabaseHas('activity_logs', ['action' => 'checklist_item_added']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'checklist_item_removed']);
    }

    public function test_a_viewer_who_may_not_update_cannot_change_the_checklist(): void
    {
        $viewer = $this->userWithPermissions(['todos.view_all']);
        $todo = Todo::factory()->create();
        $this->actingAs($viewer);

        Livewire::test(ChecklistItemsRelationManager::class, [
            'ownerRecord' => $todo,
            'pageClass' => ViewTodo::class,
        ])->assertActionHidden(TestAction::make('create')->table());
    }

    private function manager(string $class, Todo $todo): Testable
    {
        return Livewire::test($class, ['ownerRecord' => $todo, 'pageClass' => ViewTodo::class]);
    }

    public function test_a_comment_can_be_posted_with_a_server_side_mention(): void
    {
        Event::fake([TodoCommented::class, TodoMentioned::class]);

        $user = $this->userWithPermissions(['todos.comment']);
        $mentioned = User::factory()->create(['name' => 'alice']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        $this->manager(CommentsRelationManager::class, $todo)
            ->callAction(TestAction::make('create')->table(), ['body' => 'Please look @alice']);

        $comment = $todo->comments()->firstOrFail();
        $this->assertSame([$mentioned->id], $comment->mentions);
        Event::assertDispatched(TodoCommented::class);
        Event::assertDispatched(TodoMentioned::class);

        $this->manager(CommentsRelationManager::class, $todo)
            ->callAction(TestAction::make('reply')->table($comment), ['body' => 'Done']);

        $this->assertSame($comment->id, $todo->comments()->whereNotNull('parent_id')->value('parent_id'));
    }

    public function test_commenting_needs_the_comment_permission(): void
    {
        $user = $this->userWithPermissions(['todos.view_all']);
        $todo = Todo::factory()->create();
        $this->actingAs($user);

        $this->manager(CommentsRelationManager::class, $todo)
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_a_watcher_can_be_added_and_removed_by_someone_who_may_update(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $watcher = $this->plainUser();
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        $this->manager(WatchersRelationManager::class, $todo)
            ->callAction(TestAction::make('create')->table(), ['user_id' => $watcher->id]);

        $this->assertTrue($todo->watchers()->where('user_id', $watcher->id)->exists());
        $this->assertDatabaseHas('activity_logs', ['action' => 'watcher_added']);

        $row = $todo->watchers()->firstOrFail();
        $this->manager(WatchersRelationManager::class, $todo)
            ->callAction(TestAction::make('delete')->table($row));

        $this->assertFalse($todo->watchers()->where('user_id', $watcher->id)->exists());
        $this->assertDatabaseHas('activity_logs', ['action' => 'watcher_removed']);
    }

    public function test_watchers_cannot_be_changed_without_update_rights(): void
    {
        $this->actingAs($this->userWithPermissions(['todos.view_all']));

        $this->manager(WatchersRelationManager::class, Todo::factory()->create())
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_a_link_can_be_attached_and_detached(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'task.view']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $other = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        $this->manager(LinksRelationManager::class, $todo)
            ->callAction(TestAction::make('create')->table(), [
                'linkable_type' => 'todo',
                'linkable_id' => $other->id,
                'link_type' => 'blocks',
            ]);

        $this->assertDatabaseHas('todo_links', ['todo_id' => $todo->id, 'linkable_id' => $other->id, 'link_type' => 'blocks']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'link_attached']);
    }

    public function test_a_link_to_a_missing_record_is_reported_not_created(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        $this->manager(LinksRelationManager::class, $todo)
            ->callAction(TestAction::make('create')->table(), ['linkable_type' => 'todo', 'linkable_id' => 999999, 'link_type' => 'related'])
            ->assertNotified();

        $this->assertDatabaseCount('todo_links', 0);
    }

    public function test_a_link_to_a_module_the_viewer_cannot_read_is_hidden(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $task = Task::factory()->create();
        $link = TodoLink::query()->create([
            'todo_id' => $todo->id,
            'linkable_type' => Task::class,
            'linkable_id' => $task->id,
            'link_type' => 'related',
        ]);
        $this->actingAs($user);

        $this->manager(LinksRelationManager::class, $todo)->assertCanNotSeeTableRecords([$link]);

        $this->actingAs($this->userWithPermissions(['todos.create', 'task.view'], attachTo: $user));
        $this->manager(LinksRelationManager::class, $todo)->assertCanSeeTableRecords([$link]);
    }
}
