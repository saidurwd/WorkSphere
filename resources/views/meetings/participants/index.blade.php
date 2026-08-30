@extends('tyro-dashboard::layouts.admin')

@section('title', 'Participants')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.show', $meeting) }}">{{ $meeting->title }}</a>
<span class="breadcrumb-separator">/</span>
<span>Participants</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Participants</h1>
            <p class="page-description">Manage participants for {{ $meeting->title }}</p>
        </div>
        <div>
            <a href="{{ route('meetings.participants.create', $meeting) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Participant
            </a>
        </div>
    </div>
</div>

<div class="card">
    @if($participants->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Attendance</th>
                        <th>Invited At</th>
                        <th>Remarks</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($participants as $participant)
                    <tr>
                        <td>{{ $participant->user->name ?? 'N/A' }}</td>
                        <td>{{ ucwords($participant->participant_type) }}</td>
                        <td>
                            <span class="badge {{ $participant->attendance_status === 'present' ? 'badge-success' : ($participant->attendance_status === 'accepted' ? 'badge-primary' : 'badge-secondary') }}">
                                {{ ucwords($participant->attendance_status) }}
                            </span>
                        </td>
                        <td>{{ $participant->invited_at ? $participant->invited_at->format('M d, Y H:i') : 'N/A' }}</td>
                        <td>{{ $participant->remarks ?? 'N/A' }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('meetings.participants.edit', [$meeting, $participant]) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('meetings.participants.destroy', [$meeting, $participant]) }}" method="POST" style="display: inline;" id="delete-participant-form-{{ $participant->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Remove" onclick="if (confirm('Are you sure you want to remove this participant?')) { document.getElementById('delete-participant-form-{{ $participant->id }}').submit(); }">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($participants->hasPages())
        <div class="pagination">
            {{ $participants->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <p style="margin: 0; color: var(--muted-foreground);">No participants added yet.</p>
        </div>
    @endif
</div>
@endsection
