@extends('tyro-dashboard::layouts.admin')

@section('title', 'Agendas')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.show', $meeting) }}">{{ $meeting->title }}</a>
<span class="breadcrumb-separator">/</span>
<span>Agendas</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Agendas</h1>
            <p class="page-description">Manage agenda items for {{ $meeting->title }}</p>
        </div>
        <div>
            <a href="{{ route('meetings.agendas.create', $meeting) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Agenda
            </a>
        </div>
    </div>
</div>

<div class="card">
    @if($agendas->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Presented By</th>
                        <th>Est. Minutes</th>
                        <th>Status</th>
                        <th>Sort Order</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agendas as $agenda)
                    <tr>
                        <td>{{ $agenda->agenda_no }}</td>
                        <td>{{ $agenda->title }}</td>
                        <td>{{ $agenda->presentedBy->name ?? 'N/A' }}</td>
                        <td>{{ $agenda->estimated_minutes ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $agenda->status === 'completed' ? 'badge-success' : ($agenda->status === 'in_progress' ? 'badge-primary' : 'badge-secondary') }}">
                                {{ ucwords(str_replace('_', ' ', $agenda->status)) }}
                            </span>
                        </td>
                        <td>{{ $agenda->sort_order }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('meetings.agendas.edit', [$meeting, $agenda]) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('meetings.agendas.destroy', [$meeting, $agenda]) }}" method="POST" style="display: inline;" id="delete-agenda-form-{{ $agenda->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this agenda item?')) { document.getElementById('delete-agenda-form-{{ $agenda->id }}').submit(); }">
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

        @if($agendas->hasPages())
        <div class="pagination">
            {{ $agendas->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <p style="margin: 0; color: var(--muted-foreground);">No agenda items yet.</p>
        </div>
    @endif
</div>
@endsection
