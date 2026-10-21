<?php

namespace Tests\Feature;

use App\Models\Tag;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
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
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The access and validation rules the Blade task screens enforced, restated for
 * the Filament screens that replaced them.
 */
class FilamentTaskRulesTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    private function task(array $attributes = []): Task
    {
        return Task::factory()->create($attributes + ['priority' => 'medium']);
    }

    private function manager(string $class, Task $task): Testable
    {
        return Livewire::test($class, ['ownerRecord' => $task, 'pageClass' => ViewTask::class]);
    }

    // ---- who can see and change a task ------------------------------------

    public function test_a_stranger_cannot_open_a_task_they_neither_own_nor_watch(): void
    {
        $this->actingAs($this->userWithPermissions(['task.create']));
        $task = $this->task();

        $this->get("/workspace/tasks/{$task->id}")->assertNotFound();
        $this->get("/workspace/tasks/{$task->id}/edit")->assertNotFound();
    }

    public function test_an_anonymous_visitor_is_sent_to_sign_in(): void
    {
        $task = $this->task();

        $this->get("/workspace/tasks/{$task->id}")->assertRedirect('/workspace/login');
    }

    public function test_the_owner_and_the_responsible_user_may_both_edit(): void
    {
        $owner = $this->plainUser();
        $responsible = $this->plainUser();
        $task = $this->task(['user_id' => $owner->id, 'responsible_user_id' => $responsible->id]);

        foreach ([$owner, $responsible] as $actor) {
            $this->actingAs($actor);

            Livewire::test(EditTask::class, ['record' => $task->getKey()])
                ->fillForm(['title' => 'Edited by '.$actor->id])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('Edited by '.$actor->id, $task->fresh()->title);
        }
    }

    public function test_task_view_lets_you_read_a_task_but_not_change_it(): void
    {
        $this->actingAs($this->userWithPermissions(['task.view']));
        $task = $this->task();

        $this->get("/workspace/tasks/{$task->id}")->assertOk();
        $this->get("/workspace/tasks/{$task->id}/edit")->assertForbidden();

        Livewire::test(ListTasks::class)
            ->assertCanSeeTableRecords([$task])
            ->assertTableActionHidden('delete', $task);
    }

    public function test_a_super_admin_may_edit_any_task(): void
    {
        $this->actingAs($this->superAdmin());
        $task = $this->task();

        Livewire::test(EditTask::class, ['record' => $task->getKey()])
            ->fillForm(['title' => 'Admin edit'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Admin edit', $task->fresh()->title);
    }

    public function test_a_watcher_gains_visibility_but_no_editing_rights(): void
    {
        $watcher = $this->plainUser();
        $task = $this->task();
        $task->watchers()->create(['user_id' => $watcher->id]);
        $this->actingAs($watcher);

        Livewire::test(ListTasks::class)->assertCanSeeTableRecords([$task]);
        $response = $this->get("/workspace/tasks/{$task->id}");
        $response->assertOk();
        $this->get("/workspace/tasks/{$task->id}/edit")->assertForbidden();
    }

    public function test_a_stranger_cannot_manage_the_watcher_list_or_tags_or_subtasks_or_time(): void
    {
        $this->actingAs($this->userWithPermissions(['task.view']));
        $task = $this->task();

        foreach ([WatchersRelationManager::class, TagsRelationManager::class, SubtasksRelationManager::class, TimeEntriesRelationManager::class] as $class) {
            $this->manager($class, $task)->assertActionHidden(TestAction::make('create')->table());
        }
    }

    public function test_creating_a_transfer_needs_permission_to_create_tasks(): void
    {
        $this->actingAs($this->plainUser());

        $this->get('/workspace/task-transfers/create')->assertForbidden();
    }

    // ---- validation --------------------------------------------------------

    public function test_a_task_needs_a_known_status_and_a_title(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $this->actingAs($user);

        Livewire::test(CreateTask::class)
            ->fillForm([
                'title' => 'Fine', 'priority' => 'high', 'status' => 'not-a-status',
                'due_date' => now()->toDateString(), 'responsible_user_id' => $user->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['status']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_a_task_marked_complete_on_creation_is_stamped(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $this->actingAs($user);

        Livewire::test(CreateTask::class)
            ->fillForm([
                'title' => 'Already done', 'priority' => 'low', 'status' => 'completed',
                'due_date' => now()->toDateString(), 'responsible_user_id' => $user->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNotNull(Task::query()->where('title', 'Already done')->value('completed_at'));
    }

    public function test_an_empty_remark_is_rejected(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = $this->task(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $this->actingAs($user);

        $this->manager(RemarksRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['remark' => ''])
            ->assertHasFormErrors(['remark' => 'required']);

        $this->assertDatabaseCount('task_remarks', 0);
    }

    public function test_a_subtask_needs_a_title_and_is_recorded_on_the_trail(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = $this->task(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $this->actingAs($user);

        $this->manager(SubtasksRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['title' => ''])
            ->assertHasFormErrors(['title' => 'required']);

        $this->manager(SubtasksRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['title' => 'Step one']);

        $this->assertDatabaseHas('activity_logs', ['action' => 'subtask_created']);
    }

    public function test_minutes_must_be_a_positive_whole_number_and_logging_is_recorded(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = $this->task(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $this->actingAs($user);

        foreach ([0, -5, 1441] as $bad) {
            $this->manager(TimeEntriesRelationManager::class, $task)
                ->callAction(TestAction::make('create')->table(), ['minutes' => $bad, 'logged_on' => now()->toDateString()])
                ->assertHasFormErrors(['minutes']);
        }

        $this->manager(TimeEntriesRelationManager::class, $task)
            ->callAction(TestAction::make('create')->table(), ['minutes' => 30, 'logged_on' => now()->toDateString()]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'time_logged']);
    }

    // ---- tags, watchers, moves --------------------------------------------

    public function test_tagging_twice_is_idempotent_and_a_tag_is_shared_with_to_dos(): void
    {
        $user = $this->userWithPermissions(['task.create', 'todos.create']);
        $task = $this->task(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $todo = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $this->actingAs($user);

        foreach (range(1, 2) as $_) {
            $this->manager(TagsRelationManager::class, $task)
                ->callAction(TestAction::make('create')->table(), ['tag' => 'finance']);
        }

        $this->assertSame(1, $task->tags()->count());

        $todo->tags()->syncWithoutDetaching([Tag::findOrCreateByName('finance')->id]);
        $this->assertSame($task->tags()->first()->id, $todo->tags()->first()->id);
    }

    public function test_watching_twice_is_idempotent(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $other = $this->plainUser();
        $task = $this->task(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $this->actingAs($user);

        foreach (range(1, 2) as $_) {
            $this->manager(WatchersRelationManager::class, $task)
                ->callAction(TestAction::make('create')->table(), ['user_id' => $other->id]);
        }

        $this->assertSame(1, $task->watchers()->where('user_id', $other->id)->count());
    }

    public function test_a_task_cannot_become_its_own_ancestor_or_descendant_but_can_move_elsewhere(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $own = ['user_id' => $user->id, 'responsible_user_id' => $user->id];
        $root = $this->task($own);
        $child = $this->task($own + ['parent_id' => $root->id]);
        $grandchild = $this->task($own + ['parent_id' => $child->id]);
        $unrelated = $this->task($own);
        $this->actingAs($user);

        // Itself is not even offered; its child and a deep descendant are refused.
        Livewire::test(ViewTask::class, ['record' => $root->getKey()])
            ->callAction('reparent', ['parent_id' => $root->id])
            ->assertHasActionErrors();
        $this->assertNull($root->fresh()->parent_id);

        foreach ([$child->id, $grandchild->id] as $candidate) {
            Livewire::test(ViewTask::class, ['record' => $root->getKey()])
                ->callAction('reparent', ['parent_id' => $candidate])
                ->assertNotified();

            $this->assertNull($root->fresh()->parent_id);
        }

        Livewire::test(ViewTask::class, ['record' => $root->getKey()])
            ->callAction('reparent', ['parent_id' => $unrelated->id]);

        $this->assertSame($unrelated->id, $root->fresh()->parent_id);
    }

    public function test_a_stranger_cannot_reparent_someone_elses_task(): void
    {
        $this->actingAs($this->userWithPermissions(['task.view']));
        $task = $this->task();

        Livewire::test(ViewTask::class, ['record' => $task->getKey()])->assertActionHidden('reparent');
    }
}
