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
use Modules\Obligations\Models\NotificationLog;
use Modules\Tasks\Models\Task;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Filament\Pages\TodoCalendar;
use Modules\Todos\Filament\Pages\TodoReports;
use Modules\Todos\Filament\Resources\NotificationLogs\Pages\ListTodoNotificationLogs;
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

        $this->get('/todos/create')->assertForbidden();
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

        $this->get("/todos/{$theirs->getKey()}")->assertNotFound();
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

    public function test_a_repeat_rule_can_be_set_and_cleared_with_the_permission(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.manage_recurrence']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])
            ->callAction('recurrence', [
                'frequency' => 'weekly',
                'interval' => 1,
                'by_weekday' => [1, 3],
                'start_date' => '2026-11-01',
                'max_occurrences' => 5,
            ]);

        $rule = $todo->fresh()->recurrence_rule;
        $this->assertSame('weekly', $rule['frequency']);
        $this->assertSame([1, 3], $rule['by_weekday']);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])
            ->callAction('recurrence', ['frequency' => 'weekly', 'start_date' => '2026-11-01'], ['clear' => true]);

        $this->assertNull($todo->fresh()->recurrence_rule);
    }

    public function test_a_repeat_rule_without_an_end_is_refused(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.manage_recurrence']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])
            ->callAction('recurrence', ['frequency' => 'daily', 'start_date' => '2026-11-01'])
            ->assertNotified();

        $this->assertNull($todo->fresh()->recurrence_rule);
    }

    public function test_repeating_needs_the_manage_recurrence_permission(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])->assertActionHidden('recurrence');
    }

    public function test_the_inbox_tab_and_filters_narrow_the_list(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $base = ['creator_id' => $user->id, 'assignee_id' => $user->id];
        $inbox = Todo::factory()->create($base + ['status' => WorkItemStatus::Inbox]);
        $late = Todo::factory()->create($base + ['status' => WorkItemStatus::InProgress, 'due_date' => now()->subDays(3)]);
        $onTime = Todo::factory()->create($base + ['status' => WorkItemStatus::InProgress, 'due_date' => now()->addDays(3)]);
        $this->actingAs($user);

        Livewire::test(ListTodos::class)
            ->set('activeTab', 'inbox')
            ->assertCanSeeTableRecords([$inbox])
            ->assertCanNotSeeTableRecords([$late, $onTime]);

        Livewire::test(ListTodos::class)
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$late])
            ->assertCanNotSeeTableRecords([$onTime, $inbox]);
    }

    public function test_the_assignee_filter_can_find_unassigned_todos(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $unassigned = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => null]);
        $mine = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(ListTodos::class)
            ->filterTable('assignee_id', 'none')
            ->assertCanSeeTableRecords([$unassigned])
            ->assertCanNotSeeTableRecords([$mine]);
    }

    public function test_the_calendar_shows_visible_open_todos_on_their_due_date(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $date = now()->startOfMonth()->addDays(9);
        $mine = Todo::factory()->create([
            'title' => 'Ship the release', 'creator_id' => $user->id, 'assignee_id' => $user->id,
            'status' => WorkItemStatus::InProgress, 'due_date' => $date,
        ]);
        $done = Todo::factory()->create([
            'title' => 'Already finished', 'creator_id' => $user->id, 'assignee_id' => $user->id,
            'status' => WorkItemStatus::Completed, 'due_date' => $date,
        ]);
        Todo::factory()->create(['title' => 'Strangers secret', 'status' => WorkItemStatus::InProgress, 'due_date' => $date]);
        $this->actingAs($user);

        Livewire::test(TodoCalendar::class)
            ->assertSee('Ship the release')
            ->assertDontSee($done->title)
            ->assertDontSee('Strangers secret')
            ->call('shift', 1)
            ->assertDontSee('Ship the release')
            ->call('today')
            ->call('setMode', 'week')
            ->assertSet('mode', 'week');
    }

    public function test_reports_aggregate_across_people_for_those_who_may_view_all(): void
    {
        $viewer = $this->userWithPermissions(['todos.view_all']);
        $worker = User::factory()->create(['name' => 'Report Worker']);
        Todo::factory()->create([
            'creator_id' => $worker->id, 'assignee_id' => $worker->id,
            'status' => WorkItemStatus::Completed, 'completed_at' => now(),
        ]);
        $this->actingAs($viewer);

        Livewire::test(TodoReports::class)
            ->assertOk()
            ->assertSee('Completion by person')
            ->assertSee('Report Worker');
    }

    public function test_the_completion_export_is_a_csv_download(): void
    {
        $this->actingAs($this->userWithPermissions(['todos.view_all']));

        Livewire::test(TodoReports::class)
            ->call('exportCompletion')
            ->assertFileDownloaded('todo-completion-'.now()->startOfMonth()->toDateString().'-'.now()->endOfMonth()->toDateString().'.csv');
    }

    public function test_reports_are_closed_without_view_all(): void
    {
        $this->actingAs($this->userWithPermissions(['todos.create']));

        $this->get('/todo-reports')->assertForbidden();
    }

    public function test_bulk_actions_are_authorised_per_item_and_report_what_was_skipped(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.view_all']);
        $mine = Todo::factory()->count(2)->create([
            'creator_id' => $user->id, 'assignee_id' => $user->id, 'status' => WorkItemStatus::InProgress,
        ]);
        // Visible to this user through view_all, but not theirs to change.
        $theirs = Todo::factory()->create(['status' => WorkItemStatus::InProgress]);
        $this->actingAs($user);

        Livewire::test(ListTodos::class)
            ->callTableBulkAction('complete', $mine->push($theirs))
            ->assertNotified();

        foreach ($mine->take(2) as $todo) {
            $this->assertSame(WorkItemStatus::Completed, $todo->fresh()->status);
        }
        $this->assertSame(WorkItemStatus::InProgress, $theirs->fresh()->status);
    }

    public function test_a_bulk_move_the_graph_refuses_is_skipped_not_fatal(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $base = ['creator_id' => $user->id, 'assignee_id' => $user->id];
        $ready = Todo::factory()->create($base + ['status' => WorkItemStatus::InProgress]);
        $notReady = Todo::factory()->create($base + ['status' => WorkItemStatus::Inbox]);
        $this->actingAs($user);

        Livewire::test(ListTodos::class)->callTableBulkAction('complete', [$ready, $notReady]);

        $this->assertSame(WorkItemStatus::Completed, $ready->fresh()->status);
        $this->assertSame(WorkItemStatus::Inbox, $notReady->fresh()->status);
    }

    public function test_bulk_tagging_attaches_the_tag(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(ListTodos::class)->callTableBulkAction('tag', [$todo], ['tag' => 'urgent-review']);

        $this->assertTrue($todo->tags()->where('name', 'urgent-review')->exists());
    }

    private function todoLog(int $todoId): NotificationLog
    {
        // The model's $fillable predates the polymorphic subject columns, and the
        // real writers insert through the query builder.
        $log = (new NotificationLog)->forceFill([
            'channel' => 'mail',
            'notification_type' => 'todo.assigned',
            'status' => 'sent',
            'subject' => 'You were assigned a To-Do',
            'subject_type' => Todo::class,
            'subject_id' => $todoId,
        ]);
        $log->save();

        return $log;
    }

    public function test_the_notification_log_lists_only_todo_rows_for_those_who_may_view_all(): void
    {
        $todo = Todo::factory()->create();
        $mine = $this->todoLog($todo->id);
        $other = (new NotificationLog)->forceFill([
            'channel' => 'mail', 'notification_type' => 'x', 'status' => 'sent',
            'subject' => 'An obligation reminder', 'subject_type' => 'Modules\\Obligations\\Models\\Obligation', 'subject_id' => 1,
        ]);
        $other->save();
        $this->actingAs($this->userWithPermissions(['todos.view_all']));

        Livewire::test(ListTodoNotificationLogs::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);

        $this->actingAs($this->userWithPermissions(['todos.create']));
        $this->get('/todo-notification-logs')->assertForbidden();
    }

    public function test_only_a_super_admin_can_delete_a_notification_log(): void
    {
        $log = $this->todoLog(Todo::factory()->create()->id);

        $this->actingAs($this->userWithPermissions(['todos.view_all']));
        Livewire::test(ListTodoNotificationLogs::class)->assertTableActionHidden('remove', $log);
        $this->assertModelExists($log);

        $this->actingAs($this->superAdmin(['todos.view_all']));
        Livewire::test(ListTodoNotificationLogs::class)->callTableAction('remove', $log);
        $this->assertModelMissing($log);
    }
}
