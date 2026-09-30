@extends('layouts.app')

@section('title', 'New Project')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Projects', 'url' => route('projects.index')],
        ['label' => 'New Project'],
    ];
@endphp

@section('content')
<x-page-header title="New Project" subtitle="Create a new project." />

<div class="card">
    <div class="card-body">
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                @error('name') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="6">{{ old('description') }}</textarea>
                @error('description') <p class="text-danger small mt-1">{{ $message }}</p> @enderror
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>
@endsection
