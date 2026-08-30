@extends('tyro-dashboard::layouts.admin')

@section('title', 'Decisions')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.show', $meeting) }}">{{ $meeting->title }}</a>
<span class="breadcrumb-separator">/</span>
<span>Decisions</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Decisions</h1>
            <p class="page-description">Manage decisions for {{ $meeting->title }}</p>
        </div>
        <div>
            <a href="{{ route('meetings.decisions.create', $meeting) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Decision
            </a>
        </div>
    </div>
</div>

<div class="card">
    @if($decisions->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Approved By</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($decisions as $decision)
                    <tr>
                        <td>{{ $decision->decision_no }}</td>
                        <td>{{ $decision->decision_title }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $decision->decision_type)) }}</td>
                        <td>
                            <span class="badge {{ $decision->decision_status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                {{ ucwords($decision->decision_status) }}
                            </span>
                        </td>
                        <td>{{ $decision->decision_date ? $decision->decision_date->format('M d, Y') : 'N/A' }}</td>
                        <td>{{ $decision->approvedBy->name ?? 'N/A' }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('meetings.decisions.edit', [$meeting, $decision]) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('meetings.decisions.destroy', [$meeting, $decision]) }}" method="POST" style="display: inline;" id="delete-decision-form-{{ $decision->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this decision?')) { document.getElementById('delete-decision-form-{{ $decision->id }}').submit(); }">
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

        @if($decisions->hasPages())
        <div class="pagination">
            {{ $decisions->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <p style="margin: 0; color: var(--muted-foreground);">No decisions recorded yet.</p>
        </div>
    @endif
</div>
@endsection
