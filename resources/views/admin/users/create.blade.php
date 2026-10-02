@extends('layouts.app')

@section('title', 'Create User')

@section('content')
    <x-page-header title="New User" subtitle="Create a new system user account." icon="person-plus" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-form.input name="name" label="Full Name" placeholder="John Doe" required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.input name="email" label="Email Address" type="email" placeholder="john@example.com" required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.input name="password" label="Password" type="password" required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.input name="password_confirmation" label="Confirm Password" type="password" required />
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="avatar">Profile Picture</label>
                        <input type="file" name="avatar" id="avatar" class="form-control" accept="image/*">
                        <div class="form-text">Upload a profile picture (max 2MB). JPG, PNG, or GIF.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required />
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="roles[]">Roles</label>
                        <x-form.select name="roles[]" :options="$roles->pluck('name', 'id')->all()" placeholder="Select roles" multiple />
                        <div class="form-text">Hold Ctrl/Cmd to select multiple roles.</div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <x-btn type="submit" icon="check2">Save User</x-btn>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection