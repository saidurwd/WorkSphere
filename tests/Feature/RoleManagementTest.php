<?php

namespace Tests\Feature;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        $this->actingAs($this->administrator());

        $permissions = Permission::factory()->count(3)->create();

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Read-only across the platform.',
                'permissions' => $permissions->pluck('id')->map(strval(...))->all(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::query()->where('slug', 'auditor')->firstOrFail();

        // The point of the fix: no exception, AND the grants actually exist.
        $this->assertCount(3, $role->permissions);
    }

    public function test_a_role_can_be_created_without_permissions(): void
    {
        $this->actingAs($this->administrator());

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Empty', 'slug' => 'empty-role'])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::query()->where('slug', 'empty-role')->firstOrFail();

        $this->assertCount(0, $role->permissions);
        $this->assertSame('Empty', $role->name);
    }

    public function test_a_role_can_be_saved_with_permissions(): void
    {
        $this->actingAs($this->administrator());

        $role = Role::factory()->create(['name' => 'Before', 'slug' => 'editable']);
        $permissions = Permission::factory()->count(2)->create();

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm([
                'name' => 'After',
                'permissions' => $permissions->pluck('id')->map(strval(...))->all(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $role->refresh();

        $this->assertSame('After', $role->name);
        $this->assertCount(2, $role->permissions);
    }

    public function test_saving_replaces_the_permission_set_rather_than_adding_to_it(): void
    {
        $this->actingAs($this->administrator());

        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(4)->create();

        $role->syncPermissions($permissions->take(3)->pluck('id')->all());
        $this->assertCount(3, $role->permissions);

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm(['permissions' => $permissions->take(1)->pluck('id')->map(strval(...))->all()])
            ->call('save')
            ->assertHasNoFormErrors();

        // Additive syncing would make it impossible to REMOVE a grant, which is
        // how a demotion silently fails to take effect.
        $this->assertCount(1, $role->fresh()->permissions);
    }

    public function test_unticking_every_permission_removes_them_all(): void
    {
        $this->actingAs($this->administrator());

        $role = Role::factory()->create();
        $role->syncPermissions(Permission::factory()->count(2)->create()->pluck('id')->all());

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm(['permissions' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        // An empty array means "this role has no permissions", which is a
        // legitimate thing to do. Ignoring it would make the form look broken.
        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_editing_other_fields_keeps_the_existing_permissions(): void
    {
        $this->actingAs($this->administrator());

        $role = Role::factory()->create();
        $role->syncPermissions(Permission::factory()->count(2)->create()->pluck('id')->all());

        // The edit form is pre-filled with the current grants, so a rename alone
        // must not revoke them.
        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

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
        $this->actingAs($this->administrator());

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => null, 'slug' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'required']);
    }

    public function test_a_duplicate_slug_is_refused(): void
    {
        Role::factory()->create(['slug' => 'taken']);

        $this->actingAs($this->administrator());

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Another', 'slug' => 'taken'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $this->actingAs($this->administrator());

        // The super-admin role already exists — this test's own helper created one.
        $role = Role::query()->where('slug', 'super-admin')->firstOrFail();

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->assertActionHidden('delete');

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
