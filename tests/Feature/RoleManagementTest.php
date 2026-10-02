<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Saving a role from the administration screen.
 *
 * This screen was entirely broken: `RoleController::store()` and `update()` passed
 * the WHOLE validated payload to the model, and that payload includes
 * `permissions`. Eloquent is right to refuse — `permissions` is a relation, not a
 * column on `roles` — so creating a role and saving one both threw
 * `MassAssignmentException: Add fillable property [permissions]`.
 *
 * The tempting fix is to add `permissions` to `$fillable`. That silences the
 * exception and leaves the pivot UNWRITTEN: the screen would appear to work and
 * grant nothing, which is the failure mode this file is written to prevent. The
 * pivot is written through `syncPermissions()` instead, and there is a test here
 * proving the grants actually land.
 */
class RoleManagementTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function administrator(): User
    {
        return $this->superAdmin(['role.manage', 'privilege.manage']);
    }

    public function test_a_role_can_be_created_with_permissions(): void
    {
        $admin = $this->administrator();

        $permissions = Permission::factory()->count(3)->create();

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Auditor',
            'slug' => 'auditor',
            'description' => 'Read-only across the platform.',
            'permissions' => $permissions->pluck('id')->all(),
        ]);

        $response->assertRedirect(route('admin.roles.index'))->assertSessionHas('success');

        $role = Role::query()->where('slug', 'auditor')->firstOrFail();

        // The point of the fix: no exception, AND the grants actually exist.
        $this->assertTrue($role->exists);
        $this->assertCount(3, $role->permissions);
    }

    public function test_a_role_can_be_created_without_permissions(): void
    {
        $this->actingAs($this->administrator())
            ->post(route('admin.roles.store'), ['name' => 'Empty', 'slug' => 'empty-role'])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::query()->where('slug', 'empty-role')->firstOrFail();

        $this->assertCount(0, $role->permissions);
        $this->assertSame('Empty', $role->name);
    }

    public function test_a_role_can_be_saved_with_permissions(): void
    {
        $admin = $this->administrator();

        $role = Role::factory()->create(['name' => 'Before', 'slug' => 'editable']);
        $permissions = Permission::factory()->count(2)->create();

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => 'After',
            'slug' => 'editable',
            'permissions' => $permissions->pluck('id')->all(),
        ])->assertRedirect(route('admin.roles.index'))->assertSessionHas('success');

        $role->refresh();

        $this->assertSame('After', $role->name);
        $this->assertCount(2, $role->permissions);
    }

    public function test_saving_replaces_the_permission_set_rather_than_adding_to_it(): void
    {
        $admin = $this->administrator();

        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(4)->create();

        $role->syncPermissions($permissions->take(3)->pluck('id')->all());
        $this->assertCount(3, $role->permissions);

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions' => $permissions->take(1)->pluck('id')->all(),
        ])->assertRedirect();

        // Additive syncing would make it impossible to REMOVE a grant, which is
        // how a demotion silently fails to take effect.
        $this->assertCount(1, $role->fresh()->permissions);
    }

    public function test_unticking_every_permission_removes_them_all(): void
    {
        $admin = $this->administrator();

        $role = Role::factory()->create();
        $role->syncPermissions(Permission::factory()->count(2)->create()->pluck('id')->all());

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions' => [],
        ])->assertRedirect();

        // An empty array means "this role has no permissions", which is a
        // legitimate thing to do. Ignoring it would make the form look broken.
        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_an_update_that_omits_permissions_leaves_them_alone(): void
    {
        $admin = $this->administrator();

        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(2)->create();
        $role->syncPermissions($permissions->pluck('id')->all());

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => 'Renamed',
            'slug' => $role->slug,
        ])->assertRedirect();

        // A partial edit from another screen must not silently revoke grants.
        $this->assertSame('Renamed', $role->fresh()->name);
        $this->assertCount(2, $role->fresh()->permissions);
    }

    public function test_a_stale_permission_id_is_dropped_rather_than_crashing(): void
    {
        $role = Role::factory()->create();

        // A form submitted against a permission deleted since it was rendered. The
        // foreign key would otherwise turn a save into a 500.
        $attached = $role->syncPermissions([999999]);

        $this->assertSame(0, $attached);
        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_permissions_are_replaced_atomically(): void
    {
        $role = Role::factory()->create();
        $role->syncPermissions(Permission::factory()->count(2)->create()->pluck('id')->all());

        $keep = Permission::factory()->create();

        $role->syncPermissions([$keep->id]);

        // Delete-then-insert with no transaction leaves a role with NO permissions
        // if the insert fails between them. `syncPermissions()` wraps both.
        $this->assertSame(1, RolePermission::query()->where('role_id', $role->id)->count());
        $this->assertSame($keep->id, $role->fresh()->permissions->first()->id);
    }

    public function test_a_role_name_and_slug_are_required(): void
    {
        $this->actingAs($this->administrator())
            ->post(route('admin.roles.store'), [])
            ->assertSessionHasErrors(['name', 'slug']);
    }

    public function test_a_duplicate_slug_is_refused(): void
    {
        Role::factory()->create(['slug' => 'taken']);

        $this->actingAs($this->administrator())
            ->post(route('admin.roles.store'), ['name' => 'Another', 'slug' => 'taken'])
            ->assertSessionHasErrors('slug');
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $admin = $this->administrator();

        // The super-admin role already exists — this test's own helper created one.
        $role = Role::query()->where('slug', 'super-admin')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_permissions_are_not_a_mass_assignable_column(): void
    {
        // The assertion that would have caught the original bug at the model layer:
        // `permissions` is a relation, so it must never be writable through the
        // model's own mass-assignment path.
        $this->assertNotContains('permissions', (new Role)->getFillable());
        $this->assertNotContains('permissions', (new Role)->getGuarded());

        // And `preventSilentlyDiscardingAttributes` is on in the testing
        // environment, so a stray attribute would raise rather than vanish.
        $this->expectException(MassAssignmentException::class);

        Role::query()->create(['name' => 'x', 'slug' => 'x', 'permissions' => [1]]);
    }
}
