@extends('layouts.app')

@section('title')
    Edit User: {{ $user->name }}
@endsection

@section('content')
    <x-page-header title="Edit User" subtitle="{{ $user->name }}" icon="person-gear" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-form.input name="name" label="Full Name" :value="$user->name" required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.input name="email" label="Email Address" type="email" :value="$user->email" required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.input name="password" label="New Password (leave blank to keep current)" type="password" />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.input name="password_confirmation" label="Confirm New Password" type="password" />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$user->status" required />
                    </div>

                    <div class="col-12">
                        <label class="form-label">Roles</label>
                        <x-form.select name="roles[]" :options="$roles->pluck('name', 'id')->all()" :value="$user->roles->pluck('id')->all()" placeholder="Select roles" multiple />
                        <div class="form-text">Hold Ctrl/Cmd to select multiple roles.</div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">Cancel</a>
                            <x-btn type="submit" icon="check2">Save Changes</x-btn>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection