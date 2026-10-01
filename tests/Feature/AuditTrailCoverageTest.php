<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The audit trail had zero write call sites before Phase 2, so this is the net
 * that stops it regressing. Work items must land on BOTH trails: `tyro_audit_logs`
 * for the security record and `activity_logs` for the user-facing timeline.
 */
class AuditTrailCoverageTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array<string, array{class-string, class-string}>
     */
    public static function workItemProvider(): array
    {
        return [
            'task' => [Task::class, TaskFactory::class],
            'project' => [Project::class, ProjectFactory::class],
            'meeting' => [Meeting::class, MeetingFactory::class],
            'obligation' => [Obligation::class, ObligationFactory::class],
        ];
    }

    public function test_updating_a_task_writes_an_audit_row_with_old_and_new_values(): void
    {
        $actor = $this->plainUser();
        $this->actingAs($actor);

        $task = TaskFactory::new()->create(['status' => 'pending']);
        $before = AuditLog::query()->where('auditable_type', Task::class)->count();

        $task->update(['status' => 'completed']);

        $log = AuditLog::query()
            ->where('auditable_type', Task::class)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertGreaterThan($before, AuditLog::query()->where('auditable_type', Task::class)->count());
        $this->assertSame($task->id, $log->auditable_id);
        $this->assertSame('pending', $log->old_values['status']);
        $this->assertSame('completed', $log->new_values['status']);
        $this->assertSame($actor->id, $log->user_id);
    }

    public function test_updating_a_task_also_writes_an_activity_row(): void
    {
        $this->actingAs($this->plainUser());

        $task = TaskFactory::new()->create(['status' => 'pending']);
        $task->update(['status' => 'in_progress']);

        $row = ActivityLog::query()
            ->where('module_name', 'Task')
            ->where('record_id', $task->id)
            ->where('action', 'updated')
            ->firstOrFail();

        $this->assertSame('pending', $row->old_value['status']);
        $this->assertSame('in_progress', $row->new_value['status']);
    }

    #[DataProvider('workItemProvider')]
    public function test_creating_a_work_item_is_recorded_on_both_trails(string $model, string $factory): void
    {
        $this->actingAs($this->plainUser());

        $record = $factory::new()->create();

        $this->assertDatabaseHas('tyro_audit_logs', [
            'auditable_type' => $model,
            'auditable_id' => $record->getKey(),
            'event' => 'created',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => class_basename($model),
            'record_id' => $record->getKey(),
            'action' => 'created',
        ]);
    }

    #[DataProvider('workItemProvider')]
    public function test_deleting_a_work_item_is_recorded_on_both_trails(string $model, string $factory): void
    {
        $this->actingAs($this->plainUser());

        $record = $factory::new()->create();
        $record->delete();

        $this->assertDatabaseHas('tyro_audit_logs', [
            'auditable_type' => $model,
            'auditable_id' => $record->getKey(),
            'event' => 'deleted',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => class_basename($model),
            'record_id' => $record->getKey(),
            'action' => 'deleted',
        ]);
    }

    #[DataProvider('workItemProvider')]
    public function test_an_unchanged_save_records_nothing(string $model, string $factory): void
    {
        $this->actingAs($this->plainUser());

        $record = $factory::new()->create();
        $record->save();

        $this->assertSame(
            0,
            AuditLog::query()
                ->where('auditable_type', $model)
                ->where('auditable_id', $record->getKey())
                ->where('event', 'updated')
                ->count(),
        );
    }

    public function test_the_activity_log_reads_back_as_arrays_not_json_strings(): void
    {
        $this->actingAs($this->plainUser());

        $task = TaskFactory::new()->create(['title' => 'Original']);
        $task->update(['title' => 'Renamed']);

        $row = ActivityLog::query()->where('action', 'updated')->latest('id')->firstOrFail();

        $this->assertIsArray($row->new_value);
        $this->assertIsArray($row->old_value);
        $this->assertSame('Renamed', $row->new_value['title']);
    }
}
