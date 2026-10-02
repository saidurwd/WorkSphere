<?php

namespace Database\Seeders;

use App\Enums\Role as RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectPermissionSeeder extends Seeder
{
    /**
     * Every permission the application recognises.
     *
     * Public because it is the catalogue, not an implementation detail: the
     * `PermissionCatalogTest` proves every gate names a permission from here, and
     * the performance baseline grants the whole list so it measures the widest
     * path rather than whatever a hand-copied subset happened to allow.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'report.view',

        // System administration, one per screen. See the gates block in
        // AppServiceProvider for why these are six permissions rather than one.
        'system.health',
        'system.settings',
        'system.queue',
        'system.schedule',
        'system.flags',
        'system.tokens',
        'user.manage',
        'role.manage',
        'privilege.manage',
        'invitation.manage',
        'system.manage',
        'database.backup',
        'media.manage',
        'activity.view',
        'project.view',
        'project.create',
        'project.update',
        'project.delete',
        'task.view',
        // Reports on OTHER people's work. Distinct from `task.view`, which is
        // scoped to what the caller may see: the workload report, the workload
        // export and the four management dashboard widgets need this one.
        'task.view_all',
        'task.create',
        'task.update',
        'task.manage',
        'task.delete',
        'task.transfer',
        'task.view_notification_logs',
        'meeting.view',
        'meeting.create',
        'meeting.edit',
        'meeting.delete',
        'meeting.manage_templates',
        'meeting.manage_participants',
        'meeting.manage_agenda',
        'meeting.manage_discussion',
        'meeting.manage_decision',
        'meeting.create_action',
        'meeting.assign_action',
        'meeting.view_all_actions',
        'meeting.view_own_actions',
        'meeting.manage_minutes',
        'meeting.submit_minutes',
        'meeting.approve_minutes',
        'meeting.publish_minutes',
        'meeting.manage_types',
        'meeting.manage_tags',
        'meeting.view_reports',
        'meeting.view_notification_logs',
        'meeting.export',
        'obligation.view',
        'obligation.create',
        'obligation.update',
        'obligation.delete',
        'obligation.assign',
        'obligation.renew',
        'obligation.approve',
        'obligation.manage_documents',
        'obligation.manage_rules',
        'obligation.manage_settings',
        'obligation.view_reports',
        'obligation.view_all_departments',
        'obligation.view_notification_logs',
        // To-Do module — TODO-MODULE-SPECIFICATION.md §4. Additive: no existing
        // permission string is renamed or removed.
        'todos.view',
        'todos.view_all',
        'todos.create',
        'todos.create_for_others',
        'todos.update_own',
        'todos.update_any',
        'todos.complete',
        'todos.delete',
        'todos.restore',
        'todos.assign',
        'todos.comment',
        'todos.manage_recurrence',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $permissions = collect(self::PERMISSIONS)
                ->mapWithKeys(fn (string $name): array => [
                    $name => Permission::query()->firstOrCreate(['permission_name' => $name]),
                ]);
            $permissionIds = $permissions->pluck('id');

            $this->provisionRoles();

            $adminRoleIds = Role::query()
                ->whereIn('slug', config('authorization.admin_roles', []))
                ->pluck('id');

            RolePermission::query()->whereIn('permission_id', $permissionIds)->delete();

            foreach ($adminRoleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    RolePermission::query()->create([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            Permission::query()->whereNotIn('id', $permissionIds)->delete();
        });
    }

    /**
     * Create any role the application refers to that does not exist yet.
     *
     * The grant loop below looks roles up by slug. On a database that has never
     * had a role created through the admin UI — which is every freshly migrated
     * one, since no other seeder writes to `roles` — that lookup matched nothing
     * and the whole permission set was seeded but assigned to no one, so every
     * gated screen 403'd even for an administrator holding the role.
     */
    private function provisionRoles(): void
    {
        foreach (RoleSlug::cases() as $role) {
            Role::query()->firstOrCreate(
                ['slug' => $role->value],
                ['name' => $role->label()],
            );
        }
    }
}
