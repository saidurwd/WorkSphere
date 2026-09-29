<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectPermissionSeeder extends Seeder
{
    /** @var list<string> */
    private const PERMISSIONS = [
        'report.view',
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
        'task.create',
        'task.update',
        'task.manage',
        'task.delete',
        'task.transfer',
        'meeting.view',
        'meeting.create',
        'meeting.edit',
        'meeting.delete',
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
        'meeting.manage_templates',
        'meeting.manage_types',
        'meeting.manage_tags',
        'meeting.view_reports',
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
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $permissions = collect(self::PERMISSIONS)
                ->mapWithKeys(fn (string $name): array => [
                    $name => Permission::query()->firstOrCreate(['permission_name' => $name]),
                ]);
            $permissionIds = $permissions->pluck('id');
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
}
