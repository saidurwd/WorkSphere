<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Tasks\Events\TaskAssigned;
use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Events\TaskCreated;
use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Filament\Resources\Tasks\Pages\CreateTask;
use Modules\Tasks\Filament\Resources\Tasks\Pages\EditTask;
use Modules\Tasks\Filament\Resources\Tasks\Pages\ListTasks;
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
}
