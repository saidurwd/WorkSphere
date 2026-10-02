@extends('layouts.app')

@section('title', 'Roles')

@section('header-actions')
    <x-btn :href="route('admin.roles.create')" icon="plus-lg">New Role</x-btn>
@endsection

@section('content')
    <x-page-header title="Roles" subtitle="Manage user roles and their permissions." icon="person-badge" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">All Roles</h3>
        </div>

        <div class="card-body p-0">
            <x-datatable id="roles-table" :options="['pageLength' => 20, 'order' => [[1, 'asc']]]">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Slug</th>
                        <th scope="col">Description</th>
                        <th scope="col">Users</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>{{ $role->id }}</td>
                            <td>{{ $role->name }}</td>
                            <td><code>{{ $role->slug }}</code></td>
                            <td>{{ $role->description ?? '<span class="text-body-secondary">—</span>' }}</td>
                            <td>
                                <x-badge variant="info">{{ $role->user_roles_count }}</x-badge>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <x-icon-btn :href="route('admin.roles.show', $role)" icon="eye" label="View" />
                                    <x-icon-btn :href="route('admin.roles.edit', $role)" icon="pencil" label="Edit" />
                                    @if (! in_array($role->slug, config('authorization.admin_roles', [])))
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete role {{ $role->name }}?"
                                                    data-confirm-button="Delete" aria-label="Delete" title="Delete">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-datatable>
        </div>

        @if ($roles->hasPages())
            <div class="card-footer">
                <x-pagination :paginator="$roles" />
            </div>
        @endif
    </div>
@endsection