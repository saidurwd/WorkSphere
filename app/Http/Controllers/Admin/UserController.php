<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Users'],
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::query()->orderBy('name')->get();

        return view('admin.users.create', [
            'roles' => $roles,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Users', 'url' => route('admin.users.index')],
                ['label' => 'Create'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => $data['status'],
        ];

        if ($request->hasFile('avatar')) {
            $userData['avatar'] = $request->file('avatar')->store('user-avatars', 'public');
        }

        $user = User::query()->create($userData);

        if (! empty($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('roles.permissions', 'employee', 'responsibleTasks', 'projects');

        return view('admin.users.show', [
            'user' => $user,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Users', 'url' => route('admin.users.index')],
                ['label' => $user->name],
            ],
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $roles = Role::query()->orderBy('name')->get();

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('dashboard.index')],
                ['label' => 'Administration'],
                ['label' => 'Users', 'url' => route('admin.users.index')],
                ['label' => 'Edit'],
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $userData = array_filter($data, fn ($v) => $v !== null, ARRAY_FILTER_USE_BOTH);
        unset($userData['password'], $userData['avatar'], $userData['roles']);

        if ($request->filled('password')) {
            $userData['password'] = $data['password'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $userData['avatar'] = $request->file('avatar')->store('user-avatars', 'public');
        }

        $user->fill($userData);
        $user->save();

        if (isset($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
