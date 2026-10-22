<?php

namespace Tests\Feature;

use Database\Factories\MeetingFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingNotificationLog;
use Modules\Meetings\Models\MeetingParticipant;
use PHPUnit\Framework\Attributes\DataProvider;
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

    // ---- Notification logs: reading is permissioned, deleting is role-gated --

    /**
     * The four delivery-log screens, and the permission each one authorizes.
     *
     * @return array<string, array{string, string, list<string>}>
     */
    public static function notificationLogScreens(): array
    {
        return [
            'task' => [
                '/workspace/task-notification-logs',
                'task.view_notification_logs',
                ['task.view'],
            ],
            'meeting' => [
                '/meetings/notification-logs',
                'meeting.view_notification_logs',
                ['meeting.view'],
            ],
            'obligation' => [
                '/workspace/obligation-notifications',
                'obligation.view_notification_logs',
                ['obligation.view'],
            ],
        ];
    }

    /**
     * @param  list<string>  $insufficient
     */
    #[DataProvider('notificationLogScreens')]
    public function test_reading_a_notification_log_needs_its_own_permission(
        string $uri,
        string $permission,
        array $insufficient,
    ): void {
        // The module's own view permission is not enough: it is scoped to what the
        // caller owns or is responsible for, and a delivery log is not. Before the
        // gate existed, this user got the screen.
        $this->actingAs($this->userWithPermissions($insufficient))
            ->get($uri)
            ->assertForbidden();

        $this->actingAs($this->userWithPermissions([$permission]))
            ->get($uri)
            ->assertOk();
    }

    #[DataProvider('notificationLogScreens')]
    public function test_reading_a_notification_log_is_not_a_super_admin_privilege(
        string $uri,
        string $permission,
        array $insufficient,
    ): void {
        // Reading is an operational need — diagnosing a notification that did not
        // arrive — so it must not require the role that DELETING does. Otherwise
        // the gate is set too high and nobody below super-admin can use the screen
        // at all, which is the same defect as having no gate, pointed the other way.
        $this->actingAs($this->userWithPermissions([$permission]))
            ->get($uri)
            ->assertOk();
    }

    // ---- Obligation sub-resources ----------------------------------------

    // ---- Task transfers --------------------------------------------------

    // ---- Admin surface ----------------------------------------------------

    public function test_a_user_outside_the_admin_roles_cannot_reach_user_administration(): void
    {
        // The `admin` middleware is the outer gate.
        $this->actingAs($this->userWithPermissions(['task.view', 'user.manage', 'role.manage']))
            ->get(route('filament.admin.resources.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_no_permissions_is_still_refused(): void
    {
        // Passes the `admin` middleware, fails UserPolicy: this is the case that
        // proves the policy actually bites rather than merely decorating the
        // controller.
        $this->actingAs($this->adminWithout())
            ->get(route('filament.admin.resources.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_only_task_view_cannot_reach_user_administration(): void
    {
        $this->actingAs($this->adminWithout(['task.view']))
            ->get(route('filament.admin.resources.users.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_only_task_view_cannot_reach_role_administration(): void
    {
        $this->actingAs($this->adminWithout(['task.view']))
            ->get(route('filament.admin.resources.roles.index'))
            ->assertForbidden();
    }

    public function test_an_admin_role_holding_user_manage_reaches_user_administration(): void
    {
        $this->actingAs($this->adminWithout(['user.manage']))
            ->get(route('filament.admin.resources.users.index'))
            ->assertOk();
    }

    public function test_an_admin_role_holding_role_manage_reaches_role_administration(): void
    {
        $this->actingAs($this->adminWithout(['role.manage']))
            ->get(route('filament.admin.resources.roles.index'))
            ->assertOk();
    }

    public function test_a_super_admin_reaches_both_administration_screens(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('filament.admin.resources.users.index'))
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get(route('filament.admin.resources.roles.index'))
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
