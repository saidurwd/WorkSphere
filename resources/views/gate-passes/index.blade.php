@extends('tyro-dashboard::layouts.admin')

@section('title', 'Gate Passes')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Gate Passes</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Gate Passes</h1>
            <p class="page-description">Manage facility gate passes and track issued passes.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('gate-passes.dashboard') }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>
            <a href="{{ route('gate-passes.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Gate Pass
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('gate-passes.index') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search name, pass no, purpose..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Issue Date:</label>
                    <select name="issue_date" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Dates</option>
                        <option value="today" {{ ($filters['issue_date'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ ($filters['issue_date'] ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($filters['issue_date'] ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="future" {{ ($filters['issue_date'] ?? '') === 'future' ? 'selected' : '' }}>Future</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty($filters['search']) || !empty($filters['issue_date']))
                    <a href="{{ route('gate-passes.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($gatePasses->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pass No</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Issue Date</th>
                        <th>Prepared By</th>
                        <th>Check</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gatePasses as $gatePass)
                    <tr>
                        <td>
                            <a href="{{ route('gate-passes.edit', $gatePass) }}" style="text-decoration: none; color: inherit; font-weight: 600;">
                                {{ $gatePass->gate_pass_number }}
                            </a>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $gatePass->name }}</div>
                            @if($gatePass->address)
                                <div class="user-cell-email">{{ $gatePass->address }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $gatePass->purpose }}
                            </div>
                        </td>
                        <td>{{ $gatePass->issue_date->format('M d, Y') }}</td>
                        <td>{{ $gatePass->prepared_by ?? '—' }}</td>
                        <td>
                            @if($gatePass->isChecked())
                                <span class="badge badge-success">{{ $gatePass->checked_by }}</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('gate-passes.edit', $gatePass) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <a href="{{ route('gate-passes.print', $gatePass) }}" class="action-btn" title="Print" target="_blank" rel="noopener">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
                                    </svg>
                                </a>
                                <form action="{{ route('gate-passes.destroy', $gatePass) }}" method="POST" style="display: inline;" id="delete-gate-pass-form-{{ $gatePass->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete gate pass {{ $gatePass->gate_pass_number }}? This action cannot be undone.')) { document.getElementById('delete-gate-pass-form-{{ $gatePass->id }}').submit(); }">
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

        @if($gatePasses->hasPages())
        <div class="pagination">
            {{ $gatePasses->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            <h3 class="empty-state-title">No gate passes found</h3>
            <p class="empty-state-description">Get started by creating a new gate pass.</p>
            <a href="{{ route('gate-passes.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Gate Pass
            </a>
        </div>
    @endif
</div>
@endsection
