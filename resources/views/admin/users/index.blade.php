@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <x-page-header title="Users" subtitle="Manage system users and their roles." icon="people">
        <x-btn :href="route('admin.users.create')" icon="plus-lg">New User</x-btn>
    </x-page-header>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="user-search" class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input id="user-search" type="search" name="search" class="form-control"
                               placeholder="Search by name or email..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    @if (request('search'))
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">All Users</h3>
        </div>

        <div class="card-body p-0">
            <x-datatable id="users-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Status</th>
                        <th scope="col">Roles</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                <x-user-cell :name="$user->name" :email="$user->email" :size="32" :avatar="$user->avatar" />
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <x-badge :variant="$user->status === 'active' ? 'success' : 'secondary'">{{ ucfirst($user->status) }}</x-badge>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <x-badge variant="info" class="small">{{ $role->name }}</x-badge>
                                    @endforeach
                                    @if ($user->roles->isEmpty())
                                        <span class="text-body-secondary small">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <x-icon-btn :href="route('admin.users.show', $user)" icon="eye" label="View" />
                                    <x-icon-btn :href="route('admin.users.edit', $user)" icon="pencil" label="Edit" />
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                data-confirm="Delete user {{ $user->name }}? This cannot be undone."
                                                data-confirm-button="Delete" aria-label="Delete" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-datatable>
        </div>

        @if ($users->hasPages())
            <div class="card-footer">
                <x-pagination :paginator="$users" />
            </div>
        @endif
    </div>
@endsection