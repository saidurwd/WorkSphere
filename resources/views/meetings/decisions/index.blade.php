@extends('layouts.app')

@section('title', 'Decisions')

@section('content')
    <x-page-header title="Decisions" subtitle="Manage decisions for {{ $meeting->title }}" icon="check2-square">
        <x-btn :href="route('meetings.decisions.create', $meeting)" icon="plus-lg">New Decision</x-btn>
    </x-page-header>

    <div class="card">
        @if($decisions->count())
            <div class="card-body p-0">
                <x-datatable id="decisions-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                            <th scope="col">Date</th>
                            <th scope="col">Approved By</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($decisions as $decision)
                            <tr>
                                <td>{{ $decision->decision_no }}</td>
                                <td>{{ $decision->decision_title }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $decision->decision_type)) }}</td>
                                <td>
                                    <x-badge :variant="$decision->decision_status === 'active' ? 'success' : 'secondary'">{{ ucwords($decision->decision_status) }}</x-badge>
                                </td>
                                <td>{{ $decision->decision_date ? $decision->decision_date->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $decision->approvedBy->name ?? 'N/A' }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('meetings.decisions.edit', [$meeting, $decision])" icon="pencil" label="Edit" />
                                        <form action="{{ route('meetings.decisions.destroy', [$meeting, $decision]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete this decision?"
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

            @if ($decisions->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$decisions" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="check2-square" title="No decisions recorded yet" description="Decisions will appear here after they are created." />
            </div>
        @endif
    </div>
@endsection
