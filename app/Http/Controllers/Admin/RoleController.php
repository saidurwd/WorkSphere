<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()->withCount('userRoles')->orderBy('name')->paginate(20);

        return view('admin.roles.index', [
            'roles' => $roles,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Roles'],
            ],
        ]);
    }

    public function create(): View
    {
        $permissions = Permission::query()->orderBy('permission_name')->get();

        return view('admin.roles.create', [
            'permissions' => $permissions,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Roles', 'url' => route('admin.roles.index')],
                ['label' => 'Create'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::query()->create($data);

        if (! empty($data['permissions'])) {
            $role->rolePermissions()->createMany(
                collect($data['permissions'])->map(fn ($pid) => ['permission_id' => $pid])->all()
            );
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function show(Role $role): View
    {
        $role->load('users', 'permissions');

        return view('admin.roles.show', [
            'role' => $role,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Roles', 'url' => route('admin.roles.index')],
                ['label' => $role->name],
            ],
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::query()->orderBy('permission_name')->get();

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Roles', 'url' => route('admin.roles.index')],
                ['label' => 'Edit'],
            ],
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug,'.$role->id],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update($data);

        if (isset($data['permissions'])) {
            $role->rolePermissions()->delete();
            $role->rolePermissions()->createMany(
                collect($data['permissions'])->map(fn ($pid) => ['permission_id' => $pid])->all()
            );
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->slug, config('authorization.admin_roles', []), true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
