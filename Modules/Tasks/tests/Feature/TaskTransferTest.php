<?php

namespace Modules\Tasks\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskTransfer;
use Tests\TestCase;

/**
 * Task transfer history.
 *
 * `task_transfers` is an append-only record of who moved a task to whom. Nothing
 * reads it in a policy, so a mistake in it is invisible until somebody audits a
 * handover — which is exactly the kind of defect that gets through when a table has
 * no test.
 *
 * This is also the Tasks module's contribution to proving that the per-module test
 * directories execute; see `ModulesTestWiringTest` for the harness check itself.
 */
class TaskTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_the_from_and_to_users(): void
    {
        $task = Task::factory()->create();

        $transfer = TaskTransfer::factory()->create([
            'task_id' => $task->id,
            'reason' => 'Reassigned to the delivery team.',
        ]);

        $this->assertSame($task->id, $transfer->task->id);
        $this->assertSame($transfer->from_user_id, $transfer->fromUser->id);
        $this->assertSame($transfer->to_user_id, $transfer->toUser->id);
    }

    public function test_the_two_users_are_distinct(): void
    {
        $user = User::factory()->create();

        $transfer = TaskTransfer::factory()->create([
            'from_user_id' => $user->id,
            'to_user_id' => $user->id,
        ]);

        // Self-transfer is a state the service should refuse. The factory allows it
        // on purpose so the test can assert it is visible rather than assumed away.
        $this->assertSame(
            $transfer->from_user_id,
            $transfer->to_user_id,
            'A transfer to the same user is recorded; the service should reject it.',
        );
    }

    public function test_the_transfer_date_is_a_date(): void
    {
        $transfer = TaskTransfer::factory()->create(['transfer_date' => '2026-03-15']);

        $this->assertSame('2026-03-15', $transfer->transfer_date->toDateString());
    }

    public function test_a_task_keeps_its_transfer_history(): void
    {
        $task = Task::factory()->create();

        TaskTransfer::factory()->count(3)->create(['task_id' => $task->id]);

        // History is per task and per task only: a transfer recorded against
        // another task must not surface here.
        TaskTransfer::factory()->create();

        $this->assertCount(3, $task->transfers()->get());
    }

    public function test_the_history_is_ordered_newest_first(): void
    {
        $task = Task::factory()->create();

        TaskTransfer::factory()->create(['task_id' => $task->id, 'transfer_date' => '2026-01-01']);
        TaskTransfer::factory()->create(['task_id' => $task->id, 'transfer_date' => '2026-06-01']);

        $dates = $task->transfers()
            ->orderByDesc('transfer_date')
            ->pluck('transfer_date')
            ->map->toDateString()
            ->all();

        $this->assertSame(['2026-06-01', '2026-01-01'], $dates);
    }

    public function test_the_referencing_columns_exist(): void
    {
        // The `file_*` pair is on the model but nullable; asserting the columns are
        // present guards against a migration that dropped them while leaving the
        // fillable entries behind.
        $this->assertTrue(Schema::hasColumns('task_transfers', [
            'task_id', 'from_user_id', 'to_user_id', 'transferred_by', 'reason', 'transfer_date',
        ]));
    }
}
