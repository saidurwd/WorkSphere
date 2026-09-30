@extends('layouts.app')

@section('title')
    Role: {{ $role->name }}
@endsection

@section('header-actions')
    <x-btn :href="route('admin.roles.edit', $role)" icon="pencil" variant="outline-secondary">Edit</x-btn>
    @if (! in_array($role->slug, config('authorization.admin_roles', [])))
        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline ms-1" onsubmit="return confirm('Delete this role? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <x-btn type="submit" icon="trash3" variant="outline-danger">Delete</x-btn>
        </form>
    @endif
@endsection

@section('content')
    <x-page-header title="{{ $role->name }}" subtitle="{{ $role->slug }}" icon="person-badge" />

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Information</h3>
                </div>

                <div class="card-body">
                    <x-detail-list :items="[
                        ['label' => 'Name', 'value' => $role->name],
                        ['label' => 'Slug', 'value' => $role->slug],
                        ['label' => 'Description', 'value' => $role->description ?? '—'],
                        ['label' => 'Users Assigned', 'value' => $role->user_roles_count],
                        ['label' => 'Created', 'value' => $role->created_at?->format('M d, Y H:i')],
                        ['label' => 'Updated', 'value' => $role->updated_at?->format('M d, Y H:i')],
                    ]" />
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Permissions</h3>
                </div>

                <div class="card-body">
                    @if ($role->permissions->isNotEmpty())
                        <div class="row g-2">
                            @foreach ($role->permissions as $permission)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <x-badge variant="primary">{{ $permission->permission_name }}</x-badge>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-body-secondary mb-0">No permissions assigned.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Assigned Users</h3>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Manage Users</a>
                </div>

                <div class="card-body p-0">
                    @if ($role->users->isNotEmpty())
                        <x-datatable id="role-users-table" :options="['pageLength' => 10, 'order' => [[1, 'asc']]]">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($role->users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            <x-user-cell :name="$user->name" :email="$user->email" :size="28" />
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <x-badge :variant="$user->status === 'active' ? 'success' : 'secondary'">{{ ucfirst($user->status) }}</x-badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-datatable>
                    @else
                        <div class="card-body">
                            <p class="text-body-secondary text-center mb-0 py-4">No users assigned to this role.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection