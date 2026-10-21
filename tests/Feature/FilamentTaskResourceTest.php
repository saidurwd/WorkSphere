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
use Modules\Projects\Models\Project;
use Modules\Tasks\Events\TaskAssigned;
use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Events\TaskCreated;
use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Filament\Pages\TaskOverview;
use Modules\Tasks\Filament\Resources\Tasks\Pages\CreateTask;
use Modules\Tasks\Filament\Resources\Tasks\Pages\EditTask;
use Modules\Tasks\Filament\Resources\Tasks\Pages\ListTasks;
use Modules\Tasks\Filament\Resources\Tasks\Pages\ViewTask;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\RemarksRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\SubtasksRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\TagsRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\TimeEntriesRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\WatchersRelationManager;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament Task resource (workspace panel).
 */
class FilamentTaskResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_a_user_without_task_view_sees_only_their_own_tasks(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $mine = Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $theirs = Task::factory()->create();
        $this->actingAs($user);

        Livewire::test(ListTasks::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_task_view_sees_every_task(): void
    {
        $user = $this->userWithPermissions(['task.view']);
        $tasks = Task::factory()->count(2)->create();
        $this->actingAs($user);

        Livewire::test(ListTasks::class)->assertCanSeeTableRecords($tasks);
    }

    public function test_a_task_is_created_by_the_signed_in_user_and_announced(): void
    {
        Event::fake([TaskCreated::class]);

        $user = $this->userWithPermissions(['task.create']);
        $this->actingAs($user);

        Livewire::test(CreateTask::class)
            ->fillForm([
                'title' => 'Ship it',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => now()->addWeek()->toDateString(),
                'responsible_user_id' => $user->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = Task::query()->where('title', 'Ship it')->firstOrFail();
        $this->assertSame($user->id, $task->user_id);
        Event::assertDispatched(TaskCreated::class);
    }

    public function test_creating_needs_the_create_permission(): void
    {
        $this->actingAs($this->plainUser());

        $this->get('/workspace/tasks/create')->assertForbidden();
    }

    public function test_required_fields_are_enforced(): void
    {
        $this->actingAs($this->userWithPermissions(['task.create']));

        Livewire::test(CreateTask::class)
            ->fillForm(['title' => null, 'due_date' => null, 'responsible_user_id' => null])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'due_date' => 'required', 'responsible_user_id' => 'required']);
    }

    public function test_completing_through_edit_stamps_the_time_and_fires_completed(): void
    {
        Event::fake([TaskCompleted::class, TaskAssigned::class, TaskUpdated::class]);

        $user = $this->userWithPermissions(['task.update']);
        $task = Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id, 'status' => 'pending', 'priority' => 'medium']);
        $this->actingAs($user);

        Livewire::test(EditTask::class, ['record' => $task->getKey()])
            ->fillForm(['status' => 'completed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull($task->fresh()->completed_at);
        Event::assertDispatched(TaskCompleted::class);
        Event::assertNotDispatched(TaskUpdated::class);
    }

    public function test_reopening_clears_the_completion_time(): void
    {
        $user = $this->userWithPermissions(['task.update']);
        $task = Task::factory()->create([
            'user_id' => $user->id, 'responsible_user_id' => $user->id,
            'status' => WorkItemStatus::Completed, 'completed_at' => now(), 'priority' => 'medium',
        ]);
        $this->actingAs($user);

        Livewire::test(EditTask::class, ['record' => $task->getKey()])
            ->fillForm(['status' => 'pending'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_changing_the_responsible_user_fires_assigned_and_a_plain_edit_fires_updated(): void
    {
        Event::fake([TaskCompleted::class, TaskAssigned::class, TaskUpdated::class]);

        $user = $this->userWithPermissions(['task.update']);
        $other = $this->plainUser();
        $task = Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id, 'status' => 'pending', 'priority' => 'medium']);
        $this->actingAs($user);

        Livewire::test(EditTask::class, ['record' => $task->getKey()])
            ->fillForm(['responsible_user_id' => $other->id])
            ->call('save')
            ->assertHasNoFormErrors();
        Event::assertDispatched(TaskAssigned::class);

        Livewire::test(EditTask::class, ['record' => $task->getKey()])
            ->fillForm(['title' => 'Renamed'])
            ->call('save');
        Event::assertDispatched(TaskUpdated::class);
    }

    public function test_deleting_needs_the_delete_permission(): void
    {
        $owner = $this->userWithPermissions(['task.update']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'responsible_user_id' => $owner->id]);
        $this->actingAs($owner);

        Livewire::test(ListTasks::class)->assertTableActionHidden('delete', $task);

        $this->actingAs($this->userWithPermissions(['task.view', 'task.delete']));
        Livewire::test(ListTasks::class)->callTableAction('delete', $task);
        $this->assertModelMissing($task);
    }

    private function manager(string $class, Task $task): Testable
    {
        return Livewire::test($class, ['ownerRecord' => $task, 'pageClass' => ViewTask::class]);
    }

    private function ownedTask(User $user): Task
    {
        return Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id, 'priority' => 'medium']);
    }

    public function test_a_remark_is_added_and_mirrored_into_the_comment_thread(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = $this->ownedTask($user);
        $this->actingAs($user);

        $this->manager(RemarksRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['remark' => 'Waiting on legal']);

        $this->assertDatabaseHas('task_remarks', ['task_id' => $task->id, 'remark' => 'Waiting on legal', 'user_id' => $user->id]);
        $this->assertSame('Waiting on legal', $task->sharedComments()->value('body'));
    }

    public function test_a_stranger_cannot_add_a_remark(): void
    {
        $this->actingAs($this->plainUser());
        $task = Task::factory()->create();

        $this->manager(RemarksRelationManager::class, $task)
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_a_subtask_is_created_under_the_parent(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = $this->ownedTask($user);
        $this->actingAs($user);

        $this->manager(SubtasksRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['title' => 'Child step']);

        $child = Task::query()->where('title', 'Child step')->firstOrFail();
        $this->assertSame($task->id, $child->parent_id);
        $this->assertSame('pending', $child->status->value);
        $this->assertSame($user->id, $child->responsible_user_id);
    }

    public function test_time_is_logged_and_the_actual_minutes_follow(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = $this->ownedTask($user);
        $this->actingAs($user);

        $this->manager(TimeEntriesRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['minutes' => 90, 'logged_on' => now()->toDateString()]);

        $this->assertSame(90, $task->fresh()->actual_minutes);
        $entry = $task->timeEntries()->firstOrFail();

        $this->manager(TimeEntriesRelationManager::class, $task)
            ->callAction(TestAction::make('delete')->table($entry));

        $this->assertSame(0, (int) $task->fresh()->actual_minutes);
        $this->assertDatabaseHas('activity_logs', ['action' => 'time_removed']);
    }

    public function test_a_watcher_cannot_log_time_for_the_task(): void
    {
        $watcher = $this->userWithPermissions(['task.view']);
        $task = Task::factory()->create(['priority' => 'medium']);
        $task->watchers()->create(['user_id' => $watcher->id]);
        $this->actingAs($watcher);

        $this->manager(TimeEntriesRelationManager::class, $task)
            ->assertActionHidden(TestAction::make('create')->table());
    }

    public function test_watchers_can_be_added_and_removed_by_someone_who_manages_them(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $other = $this->plainUser();
        $task = $this->ownedTask($user);
        $this->actingAs($user);

        $this->manager(WatchersRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['user_id' => $other->id]);
        $this->assertTrue($task->watchers()->where('user_id', $other->id)->exists());

        $this->manager(WatchersRelationManager::class, $task)
            ->callAction(TestAction::make('delete')->table($task->watchers()->firstOrFail()));
        $this->assertFalse($task->watchers()->where('user_id', $other->id)->exists());
    }

    public function test_tags_are_attached_by_name_and_detached(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = $this->ownedTask($user);
        $this->actingAs($user);

        $this->manager(TagsRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['tag' => 'finance']);

        $tag = $task->tags()->where('name', 'finance')->firstOrFail();

        $this->manager(TagsRelationManager::class, $task)
            ->callAction(TestAction::make('detach')->table($tag));

        $this->assertFalse($task->tags()->where('name', 'finance')->exists());
    }

    public function test_a_move_that_would_create_a_cycle_is_refused(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $parent = $this->ownedTask($user);
        $child = Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id, 'parent_id' => $parent->id, 'priority' => 'medium']);
        $this->actingAs($user);

        Livewire::test(ViewTask::class, ['record' => $parent->getKey()])
            ->callAction('reparent', ['parent_id' => $child->id])
            ->assertNotified();

        $this->assertNull($parent->fresh()->parent_id);

        Livewire::test(ViewTask::class, ['record' => $child->getKey()])
            ->callAction('reparent', ['parent_id' => null]);

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_the_overview_counts_only_what_the_viewer_may_see(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        Task::factory()->count(2)->create(['user_id' => $user->id, 'responsible_user_id' => $user->id, 'status' => 'pending', 'priority' => 'high', 'due_date' => now()->addDay()]);
        Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id, 'status' => 'completed', 'completed_at' => now(), 'priority' => 'low']);
        Task::factory()->count(5)->create();
        $this->actingAs($user);

        Livewire::test(TaskOverview::class)
            ->assertOk()
            ->assertViewHas('total', 3)
            ->assertViewHas('completed', 1)
            ->assertViewHas('pending', 2);
    }

    public function test_the_project_breakdown_does_not_leak_projects_the_viewer_has_no_tasks_in(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $mine = Project::factory()->create(['name' => 'My Project']);
        $secret = Project::factory()->create(['name' => 'Secret Programme']);
        Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id, 'project_id' => $mine->id]);
        Task::factory()->create(['project_id' => $secret->id]);
        $this->actingAs($user);

        Livewire::test(TaskOverview::class)
            ->assertSee('My Project')
            ->assertDontSee('Secret Programme');
    }

    public function test_the_overview_lists_what_is_overdue(): void
    {
        $user = $this->userWithPermissions(['task.view']);
        Task::factory()->create(['title' => 'Long overdue thing', 'status' => 'pending', 'due_date' => now()->subDays(4)]);
        $this->actingAs($user);

        Livewire::test(TaskOverview::class)->assertSee('Long overdue thing');
    }
}
