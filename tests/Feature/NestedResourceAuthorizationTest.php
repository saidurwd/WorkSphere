<?php

namespace Tests\Feature;

use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingNotificationLog;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Obligations\Models\Vendor;
use Modules\Tasks\Models\TaskNotificationLog;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Sub-resource controllers (agendas, participants, minutes, vendors, transfers,
 * notification logs) had no authorization of any kind before this pass. Each case
 * proves a user with no rights over the parent cannot reach the child's writer.
 */
class NestedResourceAuthorizationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Meeting sub-resources -------------------------------------------

    public function test_an_unrelated_user_cannot_create_a_meeting_agenda_item(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('meetings.agendas.store', $meeting), $this->agendaPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('meeting_agendas', 0);
    }

    public function test_an_unrelated_user_cannot_update_a_meeting_agenda_item(): void
    {
        $meeting = MeetingFactory::new()->create();
        $agenda = MeetingAgenda::query()->create([
            'meeting_id' => $meeting->id,
            'agenda_no' => 1,
            'title' => 'Item',
            'status' => 'pending',
        ]);

        $this->actingAs($this->plainUser())
            ->put(route('meetings.agendas.update', [$meeting, $agenda]), $this->agendaPayload())
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_agendas', ['id' => $agenda->id, 'title' => 'Item']);
    }

    public function test_an_unrelated_user_cannot_delete_a_meeting_agenda_item(): void
    {
        $meeting = MeetingFactory::new()->create();
        $agenda = MeetingAgenda::query()->create([
            'meeting_id' => $meeting->id,
            'agenda_no' => 1,
            'title' => 'Item',
            'status' => 'pending',
        ]);

        $this->actingAs($this->plainUser())
            ->delete(route('meetings.agendas.destroy', [$meeting, $agenda]))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_agendas', ['id' => $agenda->id]);
    }

    public function test_the_organizer_can_manage_meeting_agenda_items(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $this->actingAs($organizer)
            ->post(route('meetings.agendas.store', $meeting), $this->agendaPayload())
            ->assertRedirect(route('meetings.show', $meeting));

        $this->assertDatabaseHas('meeting_agendas', ['meeting_id' => $meeting->id, 'title' => 'Budget review']);
    }

    public function test_an_unrelated_user_cannot_add_a_meeting_participant(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('meetings.participants.store', $meeting), [
                'user_id' => $this->plainUser()->id,
                'participant_type' => 'member',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('meeting_participants', 0);
    }

    public function test_an_unrelated_user_cannot_remove_a_meeting_participant(): void
    {
        $meeting = MeetingFactory::new()->create();
        $participant = MeetingParticipant::query()->create([
            'meeting_id' => $meeting->id,
            'user_id' => $this->plainUser()->id,
            'participant_type' => 'member',
        ]);

        $this->actingAs($this->plainUser())
            ->delete(route('meetings.participants.destroy', [$meeting, $participant]))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_participants', ['id' => $participant->id]);
    }

    public function test_an_unrelated_user_cannot_transition_meeting_minutes(): void
    {
        $meeting = MeetingFactory::new()->create();

        foreach (['prepare', 'submit', 'approve', 'publish', 'return'] as $action) {
            $this->actingAs($this->plainUser())
                ->post(route("meetings.minutes.$action", $meeting), ['comments' => 'no'])
                ->assertForbidden();
        }

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'minutes_status' => 'draft']);
    }

    public function test_an_unrelated_user_cannot_create_a_meeting_action_item(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('meetings.action-items.store', $meeting), $this->actionItemPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('meeting_action_items', 0);
    }

    public function test_an_unrelated_user_cannot_link_a_task_to_someone_elses_action_item(): void
    {
        $meeting = MeetingFactory::new()->create();
        $actionItem = MeetingActionItem::query()->create([
            'meeting_id' => $meeting->id,
            'action_no' => 1,
            'title' => 'Theirs',
            'priority' => 'high',
            'status' => 'open',
        ]);
        $task = TaskFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('meetings.action-items.tasks.link', [$meeting, $actionItem]), ['task_id' => $task->id])
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_action_items', ['id' => $actionItem->id, 'task_id' => null]);
    }

    public function test_an_unrelated_user_cannot_manage_meeting_tags(): void
    {
        $this->actingAs($this->plainUser())
            ->post(route('meetings.tags.store'), ['name' => 'Budget', 'color' => '#ff0000'])
            ->assertForbidden();

        $this->assertDatabaseCount('meeting_tags', 0);
    }

    public function test_an_unrelated_user_cannot_manage_meeting_types(): void
    {
        $this->actingAs($this->plainUser())
            ->post(route('meetings.types.store'), ['name' => 'Board', 'code' => 'BRD'])
            ->assertForbidden();

        $this->assertDatabaseCount('meeting_types', 0);
    }

    public function test_a_user_with_manage_tags_permission_can_manage_tags(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_tags']))
            ->post(route('meetings.tags.store'), ['name' => 'Budget', 'color' => '#ff0000'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('meeting_tags', ['name' => 'Budget']);
    }

    public function test_only_a_super_admin_can_delete_meeting_notification_logs(): void
    {
        $log = MeetingNotificationLog::query()->create([
            'meeting_id' => MeetingFactory::new()->create()->id,
            'notification_type' => 'reminder',
            'channel' => 'mail',
            'subject' => 'Test',
        ]);

        $this->actingAs($this->userWithPermissions(['meeting.view']))
            ->delete(route('meetings.notification-logs.destroy', $log))
            ->assertForbidden();

        $this->actingAs($this->superAdmin())
            ->delete(route('meetings.notification-logs.destroy', $log))
            ->assertRedirect();

        $this->assertDatabaseMissing('meeting_notification_logs', ['id' => $log->id]);
    }

    // ---- Obligation sub-resources ----------------------------------------

    public function test_an_unrelated_user_cannot_renew_an_obligation(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('obligations.renew.store', $obligation), [
                'new_start_date' => now()->addMonth()->format('Y-m-d'),
                'new_expiry_date' => now()->addYear()->format('Y-m-d'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('obligation_renewals', 0);
    }

    public function test_the_owner_can_renew_an_obligation(): void
    {
        $owner = $this->plainUser();
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('obligations.renew.store', $obligation), [
                'new_start_date' => now()->addMonth()->format('Y-m-d'),
                'new_expiry_date' => now()->addYear()->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('obligation_renewals', 1);
    }

    public function test_an_unrelated_user_cannot_manage_vendors(): void
    {
        $this->actingAs($this->plainUser())
            ->post(route('obligations.vendors.store'), [
                'vendor_name' => 'Acme',
                'contact_person' => 'Jane',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_a_user_with_manage_settings_permission_can_manage_vendors(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.manage_settings']))
            ->post(route('obligations.vendors.store'), [
                'vendor_name' => 'Acme',
                'contact_person' => 'Jane',
                'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vendors', ['vendor_name' => 'Acme']);
    }

    public function test_an_unrelated_user_cannot_delete_a_vendor(): void
    {
        $vendor = Vendor::query()->create(['vendor_name' => 'Acme']);

        $this->actingAs($this->plainUser())
            ->delete(route('obligations.vendors.destroy', $vendor))
            ->assertForbidden();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id]);
    }

    // ---- Task transfers --------------------------------------------------

    public function test_a_user_without_transfer_rights_cannot_transfer_someone_elses_task(): void
    {
        $task = TaskFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('task-transfers.store'), [
                'task_id' => $task->id,
                'to_user_id' => $this->plainUser()->id,
                'reason' => 'not mine',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('task_transfers', 0);
    }

    public function test_a_user_without_transfer_rights_cannot_create_a_transfer_at_all(): void
    {
        $task = TaskFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('task-transfers.store'), [
                'task_id' => $task->id,
                'to_user_id' => $this->plainUser()->id,
                'reason' => 'not mine',
            ])
            ->assertForbidden();
    }

    public function test_only_a_super_admin_can_delete_task_notification_logs(): void
    {
        $log = TaskNotificationLog::query()->create([
            'task_id' => TaskFactory::new()->create()->id,
            'notification_type' => 'reminder',
            'channel' => 'mail',
            'subject' => 'Test',
        ]);

        $this->actingAs($this->userWithPermissions(['task.view']))
            ->delete(route('tasks.notification-logs.destroy', $log))
            ->assertForbidden();

        $this->actingAs($this->superAdmin())
            ->delete(route('tasks.notification-logs.destroy', $log))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_notification_logs', ['id' => $log->id]);
    }

    // ---- Admin surface ----------------------------------------------------

    public function test_a_user_outside_the_admin_roles_cannot_reach_user_administration(): void
    {
        // The `admin` middleware is the outer gate.
        $this->actingAs($this->userWithPermissions(['task.view', 'user.manage', 'role.manage']))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_no_permissions_is_still_refused(): void
    {
        // Passes the `admin` middleware, fails UserPolicy: this is the case that
        // proves the policy actually bites rather than merely decorating the
        // controller.
        $this->actingAs($this->adminWithout())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_only_task_view_cannot_reach_user_administration(): void
    {
        $this->actingAs($this->adminWithout(['task.view']))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_only_task_view_cannot_reach_role_administration(): void
    {
        $this->actingAs($this->adminWithout(['task.view']))
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_user_manage_reaches_user_administration(): void
    {
        $this->actingAs($this->adminWithout(['user.manage']))
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_an_admin_role_holding_role_manage_reaches_role_administration(): void
    {
        $this->actingAs($this->adminWithout(['role.manage']))
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_a_super_admin_reaches_both_administration_screens(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function agendaPayload(): array
    {
        return [
            'agenda_no' => 1,
            'title' => 'Budget review',
            'status' => 'pending',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function actionItemPayload(): array
    {
        return [
            'action_no' => 1,
            'title' => 'Do the thing',
            'priority' => 'high',
            'status' => 'open',
        ];
    }
}
