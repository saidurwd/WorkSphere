@extends('layouts.app')

@section('title', 'Meeting Tags')

@section('content')
    <x-page-header title="Meeting Tags" subtitle="Manage tags for meetings." icon="tag">
        <x-btn :href="route('meetings.tags.create')" icon="plus-lg">New Tag</x-btn>
    </x-page-header>

    <div class="card">
        @if($tags->count())
            <div class="card-body p-0">
                <x-datatable id="tags-table" :options="['pageLength' => 20, 'order' => [[0, 'asc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Color</th>
                            <th scope="col">Active</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tags as $tag)
                            <tr>
                                <td>{{ $tag->name }}</td>
                                <td>
                                    @if($tag->color)
                                        <span class="d-inline-block rounded" style="width: 16px; height: 16px; background: {{ $tag->color }};"></span>
                                    @endif
                                    {{ $tag->color ?? 'N/A' }}
                                </td>
                                <td>
                                    <x-badge :variant="$tag->is_active ? 'success' : 'secondary'">{{ $tag->is_active ? 'Active' : 'Inactive' }}</x-badge>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('meetings.tags.edit', $tag)" icon="pencil" label="Edit" />
                                        <form action="{{ route('meetings.tags.destroy', $tag) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete this tag? This cannot be undone."
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

            @if ($tags->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$tags" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="tag" title="No tags found" description="Get started by creating a new tag.">
                    <x-btn :href="route('meetings.tags.create')" icon="plus-lg" size="sm">New Tag</x-btn>
                </x-empty-state>
            </div>
        @endif
    </div>
@endsection
