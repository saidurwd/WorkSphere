@extends('tyro-dashboard::layouts.admin')

@section('title', 'Decision Register')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.reports.index') }}">Reports</a>
<span class="breadcrumb-separator">/</span>
<span>Decision Register</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Decision Register</h1>
            <p class="page-description">Browse meeting decisions.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('meetings.reports.decisions') }}" method="GET">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search decisions..." value="{{ request('search') }}">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Type:</label>
                    <select name="decision_type" class="form-select" style="min-width: 180px;">
                        <option value="">All Types</option>
                        <option value="approved" {{ request('decision_type') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('decision_type') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="deferred" {{ request('decision_type') === 'deferred' ? 'selected' : '' }}>Deferred</option>
                        <option value="noted" {{ request('decision_type') === 'noted' ? 'selected' : '' }}>Noted</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($decisions->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Decision No</th>
                        <th>Meeting</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($decisions as $decision)
                    <tr>
                        <td><span style="font-weight: 600; font-family: monospace;">{{ $decision->decision_no }}</span></td>
                        <td>{{ $decision->meeting->title ?? 'N/A' }}</td>
                        <td>{{ $decision->decision_title }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $decision->decision_type)) }}</td>
                        <td>{{ ucwords($decision->decision_status) }}</td>
                        <td>{{ $decision->decision_date ? $decision->decision_date->format('M d, Y') : 'N/A' }}</td>
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
            <p style="margin: 0; color: var(--muted-foreground);">No decisions found.</p>
        </div>
    @endif
</div>
@endsection
