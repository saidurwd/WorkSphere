<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeetingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
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
        ];

        $roleId = DB::table('roles')->value('id');

        if ($roleId) {
            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['permission_name' => $permissionName]);
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}
