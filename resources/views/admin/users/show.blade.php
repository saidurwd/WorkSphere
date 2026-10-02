@extends('layouts.app')

@section('title')
    User: {{ $user->name }}
@endsection

@section('content')
    <x-page-header title="{{ $user->name }}" subtitle="{{ $user->email }}" icon="person">
        <x-btn :href="route('admin.users.edit', $user)" icon="pencil" variant="outline-secondary">Edit</x-btn>
        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" data-confirm="Delete this user? This cannot be undone.">
            @csrf
            @method('DELETE')
            <x-btn type="submit" icon="trash3" variant="outline-danger" class="ms-1">Delete</x-btn>
        </form>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    @if($user->avatar)
                        <img src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="user-image d-inline-flex" style="width: 120px; height: 120px; font-size: 3rem;">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                    @endif

                    <h4 class="mt-3 mb-1">{{ $user->name }}</h4>
                    <p class="text-body-secondary mb-3">{{ $user->email }}</p>

                    <div class="d-flex flex-wrap justify-content-center gap-1 mb-3">
                        @foreach ($user->roles as $role)
                            <x-badge variant="info">{{ $role->name }}</x-badge>
                        @endforeach
                        @if ($user->roles->isEmpty())
                            <x-badge variant="secondary">No roles</x-badge>
                        @endif

                        <x-badge :variant="$user->status === 'active' ? 'success' : 'secondary'">{{ ucfirst($user->status) }}</x-badge>
                    </div>

                    @if ($user->employee)
                        {{-- There is no `employees.show` route. Employee records are
                             served by one controller behind a `{resource}` parameter,
                             so the link goes to that screen pre-filtered to this
                             person's code. It previously called a route that does
                             not exist, which made the whole user detail page
                             fatal — `route()` throws while rendering, so any user
                             with an employee record was unreachable. --}}
                        <x-btn
                            :href="route('admin.reference.index', ['resource' => 'employees', 'search' => $user->employee->employee_code])"
                            variant="outline-primary"
                            size="sm"
                            icon="person-badge">View Employee Profile</x-btn>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Permissions</h3>
                </div>

                <div class="card-body">
                    @if ($user->roles->flatMap->permissions->isNotEmpty())
                        <div class="row g-2">
                            @foreach ($user->roles->flatMap->permissions->unique('id') as $permission)
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

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Activity Summary</h3>
                </div>

                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="fs-4 fw-semibold">{{ $user->responsibleTasks()->count() }}</div>
                            <div class="small text-body-secondary">Responsible Tasks</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fs-4 fw-semibold">{{ $user->projects()->count() }}</div>
                            <div class="small text-body-secondary">Owned Projects</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fs-4 fw-semibold">{{ $user->tasks()->count() }}</div>
                            <div class="small text-body-secondary">Created Tasks</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection