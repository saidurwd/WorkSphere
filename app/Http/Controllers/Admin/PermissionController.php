<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Permission::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('permission_name', 'like', "%{$search}%");
        }

        $permissions = $query->orderBy('permission_name')->paginate(30)->withQueryString();

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'permission_name' => ['required', 'string', 'max:255', 'unique:permissions,permission_name'],
        ]);

        Permission::query()->create($data);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created.');
    }

    public function show(Permission $permission): View
    {
        $permission->load('roles');

        return view('admin.permissions.show', compact('permission'));
    }

    public function edit(Permission $permission): View
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $request->validate([
            'permission_name' => ['required', 'string', 'max:255', 'unique:permissions,permission_name,'.$permission->id],
        ]);

        $permission->update($data);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted.');
    }
}
