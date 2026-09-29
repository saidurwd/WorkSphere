@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
    <x-page-header title="New Role" subtitle="Create a new role and assign permissions." icon="person-plus" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-form.input name="name" label="Role Name" placeholder="Project Manager" required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.input name="slug" label="Slug" placeholder="project-manager" required />
                        <div class="form-text">Unique identifier, lowercase with hyphens.</div>
                    </div>

                    <div class="col-12">
                        <x-form.textarea name="description" label="Description" rows="2" help="Optional description of this role's purpose." />
                    </div>

                    <div class="col-12">
                        <fieldset class="border rounded-3 p-3">
                            <legend class="float-none w-auto px-2 fw-semibold">Permissions</legend>
                            <div class="row g-2 mt-2">
                                @foreach ($permissions as $permission)
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm-{{ $permission->id }}">
                                            <label class="form-check-label small" for="perm-{{ $permission->id }}">{{ $permission->permission_name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <x-btn type="submit" icon="check2">Save Role</x-btn>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection