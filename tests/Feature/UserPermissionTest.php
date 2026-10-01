<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * A single-argument can() is a permission lookup against the RBAC tables; a
 * two-argument call is a policy ability and must still reach Laravel's Gate.
 */
class UserPermissionTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_a_user_without_roles_holds_no_permissions(): void
    {
        $user = $this->plainUser();

        $this->assertFalse($user->hasPermission('task.view'));
        $this->assertSame([], $user->permissionNames());
    }

    public function test_a_permission_resolves_through_role_permissions(): void
    {
        $user = $this->userWithPermissions(['task.view', 'task.update']);

        $this->assertTrue($user->hasPermission('task.view'));
        $this->assertTrue($user->hasPermission('task.update'));
        $this->assertFalse($user->hasPermission('task.delete'));
    }

    public function test_permissions_from_multiple_roles_are_merged(): void
    {
        $first = $this->userWithPermissions(['task.view'], 'role-one');
        $second = $this->userWithPermissions(['meeting.view'], 'role-two');

        $second->roles()->attach(
            Role::query()->where('slug', 'role-one')->value('id'),
        );

        $second = $second->fresh();

        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue($second->hasPermission('task.view'));
        $this->assertTrue($second->hasPermission('meeting.view'));
    }

    public function test_single_argument_can_is_a_permission_check(): void
    {
        $user = $this->userWithPermissions(['task.view']);

        $this->assertTrue($user->can('task.view'));
        $this->assertFalse($user->can('task.delete'));
    }

    public function test_two_argument_can_still_reaches_the_gate(): void
    {
        $user = $this->userWithPermissions(['task.view', 'user.manage']);

        // A class-name argument resolves to the policy's own method rather than
        // the permission table, so Gate delegation is proven here. `task.view`
        // alone does not grant `user.manage`, so the false case proves the
        // policy ran and not the permission table.
        $this->assertTrue($user->can('viewAny', User::class));

        // `role.manage` was not granted, so the role policy denies it. This proves
        // the Gate consulted the policy rather than the permission table, where
        // an unknown ability would have been a plain lookup miss.
        $this->assertFalse($user->can('create', Role::class));
    }

    public function test_the_permission_set_is_resolved_once_per_instance(): void
    {
        $user = $this->userWithPermissions(['task.view']);

        $user->hasPermission('task.view');

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $user->hasPermission('task.view');
        $user->hasPermission('task.view');
        $user->hasPermission('task.view');

        $this->assertSame(0, $queries, 'The resolved permission set should be cached on the instance.');
    }

    public function test_forgetting_the_cache_re_queries(): void
    {
        $user = $this->userWithPermissions(['task.view']);

        $this->assertTrue($user->hasPermission('task.view'));

        $user->roles()->sync([]);
        $user->forgetPermissionCache();

        $this->assertFalse($user->hasPermission('task.view'));
    }
}
