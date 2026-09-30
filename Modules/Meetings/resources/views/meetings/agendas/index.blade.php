@extends('layouts.app')

@section('title', 'Agendas')

@section('content')
    <x-page-header title="Agendas" subtitle="Manage agenda items for {{ $meeting->title }}" icon="journal-text">
        <x-btn :href="route('meetings.agendas.create', $meeting)" icon="plus-lg">New Agenda</x-btn>
    </x-page-header>

    <div class="card">
        @if($agendas->count())
            <div class="card-body p-0">
                <x-datatable id="agendas-table" :options="['pageLength' => 20, 'order' => [[0, 'asc']]]">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Presented By</th>
                            <th scope="col">Est. Minutes</th>
                            <th scope="col">Status</th>
                            <th scope="col">Sort Order</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agendas as $agenda)
                            <tr>
                                <td>{{ $agenda->agenda_no }}</td>
                                <td>{{ $agenda->title }}</td>
                                <td>{{ $agenda->presentedBy->name ?? 'N/A' }}</td>
                                <td>{{ $agenda->estimated_minutes ?? 'N/A' }}</td>
                                <td>
                                    <x-badge :variant="match ($agenda->status) {
                                        'completed' => 'success',
                                        'in_progress' => 'primary',
                                        default => 'secondary',
                                    }">{{ ucwords(str_replace('_', ' ', $agenda->status)) }}</x-badge>
                                </td>
                                <td>{{ $agenda->sort_order }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('meetings.agendas.edit', [$meeting, $agenda])" icon="pencil" label="Edit" />
                                        <form action="{{ route('meetings.agendas.destroy', [$meeting, $agenda]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete this agenda item?"
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

            @if ($agendas->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$agendas" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="journal-text" title="No agenda items yet" description="Agenda items will appear here when created." />
            </div>
        @endif
    </div>
@endsection
