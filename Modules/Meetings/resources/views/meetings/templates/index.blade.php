@extends('layouts.app')

@section('title', 'Meeting Templates')

@section('header-actions')
    <x-btn :href="route('meetings.templates.create')" icon="plus-lg">New Template</x-btn>
@endsection

@section('content')
    <x-page-header title="Meeting Templates" subtitle="Reusable meeting shapes with a standing agenda." icon="clipboard" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('meetings.templates.index') }}" method="GET" class="d-flex gap-2">
                <label for="template-search" class="visually-hidden">Search templates</label>
                <input id="template-search" type="search" name="search" class="form-control"
                       placeholder="Search templates…" value="{{ $filters['search'] ?? '' }}">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                @if (($filters['search'] ?? '') !== '')
                    <a href="{{ route('meetings.templates.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        @if ($templates->isEmpty())
            <div class="card-body">
                <x-empty-state icon="clipboard" title="No templates yet"
                               description="A template is a meeting shape with a standing agenda you can schedule repeatedly.">
                    <a href="{{ route('meetings.templates.create') }}" class="btn btn-sm btn-outline-secondary">Create one</a>
                </x-empty-state>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Agenda items</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $template)
                            <tr>
                                <td>
                                    <a href="{{ route('meetings.templates.show', $template) }}" class="text-decoration-none fw-semibold">
                                        {{ $template->name }}
                                    </a>
                                    @if ($template->description)
                                        <div class="small text-body-secondary text-truncate">{{ $template->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $template->meetingType?->name ?? '—' }}</td>
                                <td>{{ $template->agenda_items_count }}</td>
                                <td>
                                    <x-badge :variant="\App\Support\StatusBadge::priorityVariant($template->default_priority)">
                                        {{ \App\Support\StatusBadge::label($template->default_priority) }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :variant="$template->is_active ? 'success' : 'secondary'">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('meetings.templates.edit', $template)" icon="pencil"
                                                    label="Edit {{ $template->title ?? $template->name }}" />
                                        <x-icon-btn :href="route('meetings.templates.show', $template)" icon="eye"
                                                    label="Open {{ $template->name }}" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($templates->hasPages())
                <div class="card-footer"><x-pagination :paginator="$templates" /></div>
            @endif
        @endif
    </div>
@endsection
