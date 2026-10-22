<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament replacement for the Blade user administration screens.
 */
class FilamentUserResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_an_administrator_can_list_users(): void
    {
        $users = User::factory()->count(3)->create();

        $this->actingAs($this->superAdmin());

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    }

    public function test_a_user_without_the_permission_cannot_open_the_panel_resource(): void
    {
        $this->actingAs($this->plainUser())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_an_inactive_user_cannot_access_the_panel(): void
    {
        $user = $this->superAdmin();
        $user->update(['status' => 'inactive']);

        $this->actingAs($user)->get('/admin')->assertRedirect(route('filament.workspace.auth.login'));
        $this->assertGuest();
    }

    public function test_a_user_can_be_created_with_roles(): void
    {
        $role = Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $this->actingAs($this->superAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Person',
                'email' => 'new@example.com',
                'password' => 'secret-pass-1',
                'password_confirmation' => 'secret-pass-1',
                'status' => 'active',
                'employee_id' => Employee::factory()->create()->id,
                'roles' => [$role->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->roles->contains($role));
    }

    public function test_editing_without_a_password_keeps_the_existing_one(): void
    {
        $target = User::factory()->create();
        $hash = $target->password;

        $this->actingAs($this->superAdmin());

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $target->refresh();
        $this->assertSame('Renamed', $target->name);
        $this->assertSame($hash, $target->password);
    }
}
