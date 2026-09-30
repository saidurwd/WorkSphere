@extends('layouts.app')

@section('title', 'Participants')

@section('content')
    <x-page-header title="Participants" subtitle="Manage participants for {{ $meeting->title }}" icon="people">
        <x-btn :href="route('meetings.participants.create', $meeting)" icon="plus-lg">Add Participant</x-btn>
    </x-page-header>

    <div class="card">
        @if($participants->count())
            <div class="card-body p-0">
                <x-datatable id="participants-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Attendance</th>
                            <th scope="col">Invited At</th>
                            <th scope="col">Remarks</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            <tr>
                                <td>{{ $participant->user->name ?? 'N/A' }}</td>
                                <td>{{ ucwords($participant->participant_type) }}</td>
                                <td>
                                    <x-badge :variant="match ($participant->attendance_status) {
                                        'present' => 'success',
                                        'accepted' => 'primary',
                                        default => 'secondary',
                                    }">{{ ucwords($participant->attendance_status) }}</x-badge>
                                </td>
                                <td>{{ $participant->invited_at ? $participant->invited_at->format('M d, Y H:i') : 'N/A' }}</td>
                                <td>{{ $participant->remarks ?? 'N/A' }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('meetings.participants.edit', [$meeting, $participant])" icon="pencil" label="Edit" />
                                        <form action="{{ route('meetings.participants.destroy', [$meeting, $participant]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Remove this participant?"
                                                    data-confirm-button="Remove" aria-label="Remove" title="Remove">
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

            @if ($participants->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$participants" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="people" title="No participants added yet" description="Add participants to this meeting." />
            </div>
        @endif
    </div>
@endsection
