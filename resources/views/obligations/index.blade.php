@extends('tyro-dashboard::layouts.admin')

@section('title', 'Obligations')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Obligations</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Obligations</h1>
            <p class="page-description">Manage compliance and obligation renewals.</p>
        </div>
        <div>
            <a href="{{ route('obligations.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Obligation
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.index') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search obligations..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="upcoming" {{ ($filters['status'] ?? '') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="action_required" {{ ($filters['status'] ?? '') === 'action_required' ? 'selected' : '' }}>Action Required</option>
                        <option value="renewal_in_progress" {{ ($filters['status'] ?? '') === 'renewal_in_progress' ? 'selected' : '' }}>Renewal In Progress</option>
                        <option value="pending_approval" {{ ($filters['status'] ?? '') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="expired" {{ ($filters['status'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="renewed" {{ ($filters['status'] ?? '') === 'renewed' ? 'selected' : '' }}>Renewed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Priority:</label>
                    <select name="priority" class="form-select" style="min-width: 140px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Priorities</option>
                        <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Risk:</label>
                    <select name="risk_level" class="form-select" style="min-width: 140px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Risks</option>
                        <option value="low" {{ ($filters['risk_level'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ ($filters['risk_level'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ ($filters['risk_level'] ?? '') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ ($filters['risk_level'] ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Expiry:</label>
                    <select name="expiry_period" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Dates</option>
                        <option value="7_days" {{ ($filters['expiry_period'] ?? '') === '7_days' ? 'selected' : '' }}>Next 7 Days</option>
                        <option value="30_days" {{ ($filters['expiry_period'] ?? '') === '30_days' ? 'selected' : '' }}>Next 30 Days</option>
                        <option value="90_days" {{ ($filters['expiry_period'] ?? '') === '90_days' ? 'selected' : '' }}>Next 90 Days</option>
                        <option value="expired" {{ ($filters['expiry_period'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Type:</label>
                    <select name="obligation_type_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ (int) ($filters['obligation_type_id'] ?? 0) === $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Owner:</label>
                    <select name="owner_user_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Owners</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (int) ($filters['owner_user_id'] ?? 0) === $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Department:</label>
                    <select name="department_id" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ (int) ($filters['department_id'] ?? 0) === $department->id ? 'selected' : '' }}>
                                {{ $department->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($obligations->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Obligation No.</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Department</th>
                        <th>Owner</th>
                        <th>Expiry Date</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Risk</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($obligations as $obligation)
                    @php
                        $remaining = now()->startOfDay()->diffInDays($obligation->expiry_date, false);
                        $riskClass = match ($obligation->risk_level) {
                            'critical' => 'badge-danger',
                            'high' => 'badge-warning',
                            'medium' => 'badge-primary',
                            'low' => 'badge-secondary',
                        };
                        $priorityClass = match ($obligation->priority) {
                            'critical' => 'badge-danger',
                            'high' => 'badge-warning',
                            'medium' => 'badge-primary',
                            'low' => 'badge-secondary',
                        };
                    @endphp
                    <tr>
                        <td><a href="{{ route('obligations.show', $obligation) }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $obligation->obligation_no }}</a></td>
                        <td>{{ $obligation->title }}</td>
                        <td>{{ $obligation->type->type_name ?? 'N/A' }}</td>
                        <td>{{ $obligation->department->department_name ?? 'N/A' }}</td>
                        <td>{{ $obligation->owner->name ?? 'Unassigned' }}</td>
                        <td>{{ $obligation->expiry_date->format('M d, Y') }}</td>
                        <td>
                            @if($remaining < 0)
                                <span style="color: var(--destructive); font-weight: 600;">Expired {{ abs($remaining) }}d ago</span>
                            @elseif($remaining === 0)
                                <span style="color: var(--destructive); font-weight: 600;">Today</span>
                            @else
                                {{ $remaining }} days
                            @endif
                        </td>
                        <td><span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $obligation->status)) }}</span></td>
                        <td><span class="badge {{ $priorityClass }}">{{ ucfirst($obligation->priority) }}</span></td>
                        <td><span class="badge {{ $riskClass }}">{{ ucfirst($obligation->risk_level) }}</span></td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('obligations.show', $obligation) }}" class="action-btn" title="Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('obligations.edit', $obligation) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('obligations.destroy', $obligation) }}" method="POST" style="display: inline;" id="delete-obligation-form-{{ $obligation->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this obligation? This action cannot be undone.')) { document.getElementById('delete-obligation-form-{{ $obligation->id }}').submit(); }">
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

        @if($obligations->hasPages())
        <div class="pagination">
            {{ $obligations->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <h3 class="empty-state-title">No obligations found</h3>
            <p class="empty-state-description">Get started by creating a new obligation.</p>
            <a href="{{ route('obligations.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Obligation
            </a>
        </div>
    @endif
</div>
@endsection
