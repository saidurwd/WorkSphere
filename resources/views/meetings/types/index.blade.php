@extends('layouts.app')

@section('title', 'Meeting Types')

@section('content')
    <x-page-header title="Meeting Types" subtitle="Manage meeting type categories." icon="calendar-week">
        <x-btn :href="route('meetings.types.create')" icon="plus-lg">New Type</x-btn>
    </x-page-header>

    <div class="card">
        @if($types->count())
            <div class="card-body p-0">
                <x-datatable id="types-table" :options="['pageLength' => 20, 'order' => [[0, 'asc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Code</th>
                            <th scope="col">Color</th>
                            <th scope="col">Active</th>
                            <th scope="col">Sort Order</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($types as $type)
                            <tr>
                                <td>{{ $type->name }}</td>
                                <td><code>{{ $type->code }}</code></td>
                                <td>
                                    @if($type->color)
                                        <span class="d-inline-block rounded" style="width: 16px; height: 16px; background: {{ $type->color }};"></span>
                                    @endif
                                    {{ $type->color ?? 'N/A' }}
                                </td>
                                <td>
                                    <x-badge :variant="$type->is_active ? 'success' : 'secondary'">{{ $type->is_active ? 'Active' : 'Inactive' }}</x-badge>
                                </td>
                                <td>{{ $type->sort_order }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('meetings.types.edit', $type)" icon="pencil" label="Edit" />
                                        <form action="{{ route('meetings.types.destroy', $type) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete this meeting type? This cannot be undone."
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

            @if ($types->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$types" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="calendar-week" title="No meeting types found" description="Get started by creating a new meeting type.">
                    <x-btn :href="route('meetings.types.create')" icon="plus-lg" size="sm">New Type</x-btn>
                </x-empty-state>
            </div>
        @endif
    </div>
@endsection
