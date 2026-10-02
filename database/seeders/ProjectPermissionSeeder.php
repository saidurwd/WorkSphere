<?php

namespace Database\Seeders;

use App\Enums\Role as RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
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

    /**
     * What each non-administrator role may do.
     *
     * Without this the seeder grants everything to `admin`/`super-admin` and
     * nothing to anyone else, which is not a permission model — it is a binary
     * split between "may administer the system" and "may do nothing". Every
     * account in a seeded demo organisation is a manager, a user, an employee or
     * a viewer, so a demo run had no way to show the screens those roles are
     * meant to reach.
     *
     * Keyed by the `App\Enums\Role` slug. A slug absent from this list keeps no
     * grants at all, which is the correct reading: an unlisted role is one
     * nobody has decided the scope of yet.
     *
     * Each entry names permissions from {@see self::PERMISSIONS}. A name that is
     * not in the catalogue is skipped rather than created, so this can never
     * reintroduce a permission the pruning step below is about to remove.
     *
     * The split is by capability, not by screen:
     *
     * - `manager` runs a team: everything about tasks, meetings and obligations
     *   within that scope, including viewing other people's work, but nothing
     *   about the system itself.
     * - `user` is a senior individual contributor: their own work plus the
     *   shared records they are expected to create.
     * - `employee` is a junior contributor: create and complete their own work,
     *   read the shared boards, and nothing that changes other people's.
     * - `viewer` is read-only, for auditors and executives who need to look
     *   without being able to change anything.
     *
     * @var array<string, list<string>>
     */
    public const ROLE_PERMISSIONS = [
        RoleSlug::Manager->value => [
            'report.view',
            'activity.view',
            'project.view',
            'project.create',
            'project.update',
            'project.delete',
            'task.view',
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
            'obligation.assign',
            'obligation.renew',
            'obligation.approve',
            'obligation.manage_documents',
            'obligation.manage_rules',
            'obligation.view_reports',
            'obligation.view_all_departments',
            'obligation.view_notification_logs',
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
        ],
        RoleSlug::User->value => [
            'report.view',
            'project.view',
            'project.create',
            'project.update',
            'task.view',
            'task.create',
            'task.update',
            'task.view_notification_logs',
            'meeting.view',
            'meeting.create',
            'meeting.edit',
            'meeting.create_action',
            'meeting.view_own_actions',
            'obligation.view',
            'obligation.create',
            'obligation.update',
            'obligation.manage_documents',
            'todos.view',
            'todos.create',
            'todos.update_own',
            'todos.complete',
            'todos.delete',
            'todos.comment',
            'todos.manage_recurrence',
        ],
        RoleSlug::Employee->value => [
            'project.view',
            'task.view',
            'task.create',
            'task.update',
            'meeting.view',
            'meeting.view_own_actions',
            'obligation.view',
            'todos.view',
            'todos.create',
            'todos.update_own',
            'todos.complete',
            'todos.comment',
        ],
        RoleSlug::Viewer->value => [
            'report.view',
            'project.view',
            'task.view',
            'meeting.view',
            'meeting.view_own_actions',
            'obligation.view',
            'todos.view',
        ],
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

            $grants = [];

            foreach ($adminRoleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    $grants[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }
            }

            foreach ($this->roleGrants($permissions) as $slug => $names) {
                $roleId = Role::query()->where('slug', $slug)->value('id');

                if ($roleId === null) {
                    continue;
                }

                foreach ($names as $permissionId) {
                    $grants[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }
            }

            // `insertOrIgnore` rather than a loop of creates: the composite
            // primary key on (role_id, permission_id) makes a duplicate a
            // no-op, so re-seeding is free and cannot inflate the table.
            foreach (array_chunk($grants, 500) as $chunk) {
                RolePermission::query()->insertOrIgnore($chunk);
            }

            Permission::query()->whereNotIn('id', $permissionIds)->delete();
        });
    }

    /**
     * Resolve {@see self::ROLE_PERMISSIONS} to permission ids.
     *
     * @param  Collection<string, Permission>  $permissions
     * @return array<string, list<int>>
     */
    private function roleGrants(Collection $permissions): array
    {
        $grants = [];

        foreach (self::ROLE_PERMISSIONS as $slug => $names) {
            $grants[$slug] = collect($names)
                ->map(fn (string $name): ?int => $permissions->get($name)?->id)
                ->filter()
                ->values()
                ->all();
        }

        return $grants;
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
            Role::query()->updateOrCreate(
                ['slug' => $role->value],
                ['name' => $role->label(), 'description' => self::ROLE_DESCRIPTIONS[$role->value] ?? null],
            );
        }
    }

    /**
     * What each role is for, in the words shown on the role list.
     *
     * `roles.description` is nullable and the admin role screens render it, so a
     * seeded database left every role as a bare label with no explanation of
     * what holding it means.
     *
     * @var array<string, string>
     */
    private const ROLE_DESCRIPTIONS = [
        'super-admin' => 'Unrestricted access to every module and system setting, including role and permission management.',
        'admin' => 'Full access to the application modules and their data, without the ability to change roles or system configuration.',
        'manager' => 'Runs a team: creates and completes work, sees other people\'s work in their scope, and approves minutes and renewals.',
        'user' => 'A senior individual contributor: owns their own work and creates shared projects, meetings and obligations.',
        'employee' => 'A contributor: creates, updates and completes their own work and reads the shared boards.',
        'viewer' => 'Read-only access for auditors, clients and executives who review work without changing it.',
    ];
}
