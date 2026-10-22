<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Tasks\Filament\Resources\NotificationLogs\Pages\ListTaskNotificationLogs;
use Modules\Tasks\Filament\Resources\Transfers\Pages\CreateTaskTransfer;
use Modules\Tasks\Filament\Resources\Transfers\Pages\ListTaskTransfers;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskNotificationLog;
use Modules\Tasks\Models\TaskTransfer;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Task transfers and the task notification log in the Filament workspace panel.
 */
class FilamentTaskAdminScreensTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_a_transfer_reassigns_the_task_and_records_who_did_it(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $to = $this->plainUser();
        $task = Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(CreateTaskTransfer::class)
            ->fillForm(['task_id' => $task->id, 'to_user_id' => $to->id, 'reason' => 'Handover'])
            ->call('create')
            ->assertHasNoFormErrors();

        $transfer = TaskTransfer::query()->firstOrFail();
        $this->assertSame($user->id, $transfer->from_user_id);
        $this->assertSame($user->id, $transfer->transferred_by);
        $this->assertSame($to->id, $task->fresh()->responsible_user_id);
    }

    public function test_transferring_someone_elses_task_is_refused(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $task = Task::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateTaskTransfer::class)
            ->fillForm(['task_id' => $task->id, 'to_user_id' => $user->id, 'reason' => 'Grab it'])
            ->call('create')
            ->assertForbidden();

        $this->assertDatabaseCount('task_transfers', 0);
    }

    public function test_transfers_of_tasks_the_viewer_cannot_see_are_not_listed(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $mine = Task::factory()->create(['user_id' => $user->id, 'responsible_user_id' => $user->id]);
        $theirs = Task::factory()->create();
        $visible = TaskTransfer::factory()->create(['task_id' => $mine->id]);
        $hidden = TaskTransfer::factory()->create(['task_id' => $theirs->id]);
        $this->actingAs($user);

        Livewire::test(ListTaskTransfers::class)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_a_transfer_can_be_deleted_only_by_someone_who_may_transfer_the_task(): void
    {
        $owner = $this->userWithPermissions(['task.create']);
        $task = Task::factory()->create(['user_id' => $owner->id, 'responsible_user_id' => $owner->id]);
        $transfer = TaskTransfer::factory()->create(['task_id' => $task->id]);

        $this->actingAs($this->userWithPermissions(['task.view']));
        Livewire::test(ListTaskTransfers::class)->assertTableActionHidden('remove', $transfer);

        $this->actingAs($owner);
        Livewire::test(ListTaskTransfers::class)->assertCanSeeTableRecords([$transfer])->assertTableActionVisible('remove', $transfer)->callTableAction('remove', $transfer);
        $this->assertModelMissing($transfer);
    }

    public function test_the_notification_log_needs_its_permission_and_super_admin_deletes(): void
    {
        $log = TaskNotificationLog::factory()->create();

        $this->actingAs($this->userWithPermissions(['task.view']));
        $this->get('/task-notification-logs')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['task.view_notification_logs']));
        Livewire::test(ListTaskNotificationLogs::class)
            ->assertCanSeeTableRecords([$log])
            ->assertTableActionHidden('remove', $log);

        $this->actingAs($this->superAdmin(['task.view_notification_logs']));
        Livewire::test(ListTaskNotificationLogs::class)->callTableAction('remove', $log);
        $this->assertModelMissing($log);
    }

    public function test_delete_all_shown_removes_only_rows_matching_the_filter(): void
    {
        $failed = TaskNotificationLog::factory()->count(2)->create(['status' => 'failed']);
        $sent = TaskNotificationLog::factory()->create(['status' => 'sent']);
        $this->actingAs($this->superAdmin(['task.view_notification_logs']));

        Livewire::test(ListTaskNotificationLogs::class)
            ->filterTable('status', 'failed')
            ->callAction('deleteFiltered');

        $this->assertSame(0, TaskNotificationLog::query()->where('status', 'failed')->count());
        $this->assertModelExists($sent);
    }
}
