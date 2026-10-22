<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
                '/workspace/meeting-notification-logs',
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
