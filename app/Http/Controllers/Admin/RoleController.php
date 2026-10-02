<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

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
        $this->authorize('create', Role::class);

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
        $this->authorize('create', Role::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = DB::transaction(function () use ($data): Role {
            $role = Role::query()->create($this->roleAttributes($data));

            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function show(Role $role): View
    {
        $this->authorize('view', $role);

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
        $this->authorize('update', $role);

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
        $this->authorize('update', $role);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug,'.$role->id],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        DB::transaction(function () use ($data, $role): void {
            $role->update($this->roleAttributes($data));

            // Only re-sync when the field was actually submitted. An update that
            // omits it leaves the grants alone, which is what a partial edit from
            // another screen should do.
            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions']);
            }
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    /**
     * The role's OWN columns, with the pivot ids taken out.
     *
     * `permissions` is a RELATION, not a column on `roles`. Passing the whole
     * validated payload to the model asked Eloquent to mass-assign a column that
     * does not exist, and it threw `MassAssignmentException: Add fillable
     * property [permissions]` — so creating a role and saving one both failed
     * outright. Adding `permissions` to `$fillable` would silence the error and
     * leave the pivot unwritten, which is worse: the screen would appear to work
     * and grant nothing.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function roleAttributes(array $data): array
    {
        return Arr::only($data, ['name', 'slug', 'description']);
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if (in_array($role->slug, config('authorization.admin_roles', []), true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
