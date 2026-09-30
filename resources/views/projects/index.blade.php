@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <x-page-header title="Projects" subtitle="Manage your projects and their descriptions." icon="folder2-open">
        <x-btn :href="route('projects.create')" icon="plus-lg">New Project</x-btn>
    </x-page-header>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('projects.index') }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label for="project-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="project-search" type="search" name="search" class="form-control"
                                   placeholder="Search projects..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                        @if (request('search'))
                            <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if($projects->count())
            <div class="card-body p-0">
                <x-datatable id="projects-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Tasks</th>
                            <th scope="col">Created</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $project) }}" class="fw-semibold text-decoration-none">
                                        {{ $project->name }}
                                    </a>
                                    @if($project->description)
                                        <div class="small text-body-secondary">{{ \Illuminate\Support\Str::limit($project->description, 80) }}</div>
                                    @endif
                                </td>
                                <td>{{ $project->tasks_count }}</td>
                                <td>{{ $project->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('projects.show', $project)" icon="eye" label="View" />
                                        <x-icon-btn :href="route('projects.edit', $project)" icon="pencil" label="Edit" />
                                        <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete project "{{ $project->name }}"? This cannot be undone."
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

            @if ($projects->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$projects" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="folder2-open" title="No projects found" description="Get started by creating a new project.">
                    <x-btn :href="route('projects.create')" icon="plus-lg" size="sm">New Project</x-btn>
                </x-empty-state>
            </div>
        @endif
    </div>
@endsection
