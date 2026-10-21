<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use App\Support\StatusBadge;
use Database\Factories\TaskFactory;
use Database\Factories\TimeEntryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tasks\Models\Task;
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

    // ---- Time tracking ------------------------------------------------------

    public function test_actual_minutes_is_a_cache_of_the_logged_entries(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        TimeEntryFactory::new()->count(3)->minutes(20)->create(['task_id' => $task->id]);

        $this->assertSame(60, $task->loggedMinutes());

        $task->syncActualMinutes();

        $this->assertSame(60, $task->fresh()->actual_minutes);
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
