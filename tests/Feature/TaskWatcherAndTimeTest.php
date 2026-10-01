<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use App\Policies\TaskPolicy;
use App\Support\StatusBadge;
use Database\Factories\TaskFactory;
use Database\Factories\TimeEntryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskWatcher;
use Modules\Tasks\Models\TimeEntry;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Task watchers, time tracking and the GAP-026 widening.
 *
 * The watcher cases pin the separation that matters: a watcher gains visibility
 * and nothing else. Making `owns()` include the watcher check would hand every
 * watcher update, delete and transfer rights, which is not what "mirroring
 * todo_watchers" means.
 */
class TaskWatcherAndTimeTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Watchers -----------------------------------------------------------

    public function test_a_watcher_can_be_added(): void
    {
        $owner = $this->plainUser();
        $watcher = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id])
            ->assertRedirect();

        $this->assertDatabaseHas('task_watchers', ['task_id' => $task->id, 'user_id' => $watcher->id]);
    }

    public function test_watching_the_same_task_twice_is_idempotent(): void
    {
        $owner = $this->plainUser();
        $watcher = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id]);
        $this->actingAs($owner)->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id]);

        $this->assertSame(1, TaskWatcher::query()->where('task_id', $task->id)->count());
    }

    public function test_a_watcher_gains_visibility(): void
    {
        $owner = $this->plainUser();
        $watcher = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id]);

        $this->assertTrue(
            (new TaskPolicy)->view($watcher, $task->fresh()),
            'Watching must make the task visible.',
        );

        $this->actingAs($watcher)->get(route('tasks.show', $task))->assertOk();
    }

    public function test_a_watcher_gains_no_editing_rights(): void
    {
        $owner = $this->plainUser();
        $watcher = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id]);

        $policy = new TaskPolicy;
        $fresh = $task->fresh();

        $this->assertTrue($policy->view($watcher, $fresh));
        $this->assertFalse($policy->update($watcher, $fresh), 'Visibility must not imply authorship.');
        $this->assertFalse($policy->delete($watcher, $fresh));

        $this->actingAs($watcher)->get(route('tasks.edit', $task))->assertForbidden();
    }

    public function test_a_watcher_can_be_removed(): void
    {
        $owner = $this->plainUser();
        $watcher = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id]);

        $this->actingAs($owner)
            ->delete(route('tasks.watchers.destroy', [$task, $watcher->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_watchers', ['task_id' => $task->id, 'user_id' => $watcher->id]);
    }

    public function test_a_stranger_cannot_manage_the_watcher_list(): void
    {
        $task = TaskFactory::new()->create();
        $watcher = $this->plainUser();

        $this->actingAs($this->plainUser())
            ->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id])
            ->assertForbidden();
    }

    public function test_the_watcher_list_appears_in_the_index(): void
    {
        $owner = $this->plainUser();
        $watcher = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create(['title' => 'Watched task']);

        $this->actingAs($owner)->post(route('tasks.watchers.store', $task), ['user_id' => $watcher->id]);

        // The list filter must match TaskPolicy::view, or a watcher can open a
        // task they cannot see listed.
        $this->actingAs($watcher)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Watched task');
    }

    public function test_watching_requires_authentication(): void
    {
        $task = TaskFactory::new()->create();

        $this->post(route('tasks.watchers.store', $task), ['user_id' => 1])
            ->assertRedirect(route('login'));
    }

    // ---- Time tracking ------------------------------------------------------

    public function test_time_can_be_logged(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        $this->actingAs($user)
            ->post(route('tasks.time-entries.store', $task), [
                'minutes' => 45,
                'logged_on' => now()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', ['task_id' => $task->id, 'minutes' => 45]);
    }

    public function test_actual_minutes_is_a_cache_of_the_logged_entries(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        TimeEntryFactory::new()->count(3)->minutes(20)->create(['task_id' => $task->id]);

        $this->assertSame(60, $task->loggedMinutes());

        $task->syncActualMinutes();

        $this->assertSame(60, $task->fresh()->actual_minutes);
    }

    public function test_removing_an_entry_resyncs_the_cache(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        TimeEntryFactory::new()->count(2)->minutes(30)->create(['task_id' => $task->id]);
        $task->syncActualMinutes();
        $this->assertSame(60, $task->fresh()->actual_minutes);

        $entry = $task->timeEntries()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('tasks.time-entries.destroy', [$task, $entry]))
            ->assertRedirect();

        $this->assertSame(
            30,
            $task->fresh()->actual_minutes,
            'The cached figure must follow the log, or the two drift apart.',
        );
    }

    public function test_logging_time_is_recorded_on_the_activity_trail(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        $this->actingAs($user)->post(route('tasks.time-entries.store', $task), [
            'minutes' => 30,
            'logged_on' => now()->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Task',
            'record_id' => $task->id,
            'action' => 'time_logged',
        ]);
    }

    public function test_logging_time_requires_the_permission_or_ownership(): void
    {
        $task = TaskFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('tasks.time-entries.store', $task), [
                'minutes' => 30,
                'logged_on' => now()->format('Y-m-d'),
            ])
            ->assertForbidden();
    }

    public function test_minutes_must_be_a_positive_integer(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        $this->actingAs($user)
            ->post(route('tasks.time-entries.store', $task), [
                'minutes' => 0,
                'logged_on' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('minutes');
    }

    public function test_an_entry_belonging_to_another_task_is_404(): void
    {
        $user = $this->plainUser();
        $mine = TaskFactory::new()->ownedBy($user)->create();
        $theirs = TaskFactory::new()->create();

        $entry = $theirs->timeEntries()->create([
            'user_id' => $user->id,
            'minutes' => 10,
            'logged_on' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->delete(route('tasks.time-entries.destroy', [$mine, $entry]))
            ->assertNotFound();

        $this->assertNotNull(TimeEntry::query()->find($entry->id));
    }

    // ---- GAP-026 widening ---------------------------------------------------

    public function test_due_date_is_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('tasks', 'due_date'));

        $task = TaskFactory::new()->undated()->create();

        $this->assertNull($task->fresh()->due_date);
        $this->assertFalse($task->isOverdue());
        $this->assertFalse($task->isToday());
        $this->assertFalse($task->isUpcoming());
    }

    public function test_every_widened_status_renders_on_the_show_page(): void
    {
        // The widening added on_hold and cancelled; a view that cannot render one
        // of them throws, and only when a real task happens to carry it.
        foreach (['pending', 'in_progress', 'on_hold', 'completed', 'cancelled'] as $status) {
            $owner = $this->plainUser();
            $task = TaskFactory::new()->ownedBy($owner)->create(['status' => $status]);

            $this->actingAs($owner)->get(route('tasks.show', $task))->assertOk();
        }
    }

    public function test_on_hold_and_cancelled_are_active_state_flags(): void
    {
        $task = TaskFactory::new()->create(['status' => WorkItemStatus::OnHold]);
        $this->assertTrue($task->status->isOpen());

        $task = TaskFactory::new()->create(['status' => WorkItemStatus::Cancelled]);
        $this->assertTrue($task->status->isClosed());
    }

    public function test_the_active_scope_excludes_terminal_statuses(): void
    {
        // Deterministic rather than randomised: a single random task could land on
        // a terminal status and make this assertion depend on the seed.
        foreach (['pending', 'in_progress', 'on_hold'] as $open) {
            TaskFactory::new()->create(['status' => $open]);
        }

        foreach (['completed', 'cancelled'] as $terminal) {
            TaskFactory::new()->create(['status' => $terminal]);
        }

        // 3 open (pending, in_progress, on_hold) and 2 terminal.
        $this->assertSame(3, Task::query()->active()->count());
        $this->assertSame(1, Task::query()->status(WorkItemStatus::OnHold)->count());
        $this->assertSame(1, Task::query()->status(WorkItemStatus::Completed)->count());
        $this->assertSame(
            2,
            Task::query()->status([WorkItemStatus::Completed, WorkItemStatus::Cancelled])->count(),
        );
    }

    public function test_every_widened_status_has_a_badge_variant(): void
    {
        foreach (['pending', 'in_progress', 'on_hold', 'completed', 'cancelled'] as $status) {
            $this->assertNotSame(
                'undefined-variant',
                StatusBadge::variant($status),
                "{$status} has no badge variant.",
            );
        }
    }

    public function test_status_persists_as_the_expected_string(): void
    {
        foreach (['on_hold', 'cancelled'] as $status) {
            $task = TaskFactory::new()->create(['status' => $status]);

            // read through the query builder, so this asserts the stored value
            // rather than the cast enum instance.
            $this->assertSame(
                $status,
                DB::table('tasks')->where('id', $task->id)->value('status'),
            );
        }
    }
}
