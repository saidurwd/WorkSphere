@extends('layouts.app')

@section('title', 'Obligations')

@section('content')
    <x-page-header title="Obligations" subtitle="Manage compliance and obligation renewals." icon="file-earmark-text">
        <x-btn :href="route('obligations.create')" icon="plus-lg">New Obligation</x-btn>
    </x-page-header>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('obligations.index') }}" method="GET" id="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="obligation-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="obligation-search" type="search" name="search" class="form-control"
                                   placeholder="Search obligations..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="status-filter" class="form-label">Status</label>
                        <select id="status-filter" name="status" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="action_required" {{ request('status') === 'action_required' ? 'selected' : '' }}>Action Required</option>
                            <option value="renewal_in_progress" {{ request('status') === 'renewal_in_progress' ? 'selected' : '' }}>Renewal In Progress</option>
                            <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="renewed" {{ request('status') === 'renewed' ? 'selected' : '' }}>Renewed</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="priority-filter" class="form-label">Priority</label>
                        <select id="priority-filter" name="priority" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="risk-filter" class="form-label">Risk</label>
                        <select id="risk-filter" name="risk_level" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="low" {{ request('risk_level') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('risk_level') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('risk_level') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ request('risk_level') === 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="type-filter" class="form-label">Type</label>
                        <select id="type-filter" name="obligation_type_id" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" {{ request('obligation_type_id') == $type->id ? 'selected' : '' }}>{{ $type->type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="owner-filter" class="form-label">Owner</label>
                        <select id="owner-filter" name="owner_user_id" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('owner_user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="department-filter" class="form-label">Department</label>
                        <select id="department-filter" name="department_id" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="expiry-filter" class="form-label">Expiry</label>
                        <select id="expiry-filter" name="expiry_period" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            <option value="7_days" {{ request('expiry_period') === '7_days' ? 'selected' : '' }}>Next 7 Days</option>
                            <option value="30_days" {{ request('expiry_period') === '30_days' ? 'selected' : '' }}>Next 30 Days</option>
                            <option value="90_days" {{ request('expiry_period') === '90_days' ? 'selected' : '' }}>Next 90 Days</option>
                            <option value="expired" {{ request('expiry_period') === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                        @if(request()->hasAny(['search', 'status', 'priority', 'risk_level', 'expiry_period', 'obligation_type_id', 'owner_user_id', 'department_id']))
                            <a href="{{ route('obligations.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if($obligations->count())
            <div class="card-body p-0">
                <x-datatable id="obligations-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Obligation No.</th>
                            <th scope="col">Title</th>
                            <th scope="col">Type</th>
                            <th scope="col">Department</th>
                            <th scope="col">Owner</th>
                            <th scope="col">Expiry Date</th>
                            <th scope="col">Remaining</th>
                            <th scope="col">Status</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Risk</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($obligations as $obligation)
                            @php
                                $remaining = now()->startOfDay()->diffInDays($obligation->expiry_date, false);
                            @endphp
                            <tr>
                                <td><a href="{{ route('obligations.show', $obligation) }}" class="fw-semibold text-decoration-none">{{ $obligation->obligation_no }}</a></td>
                                <td>{{ $obligation->title }}</td>
                                <td>{{ $obligation->type->type_name ?? 'N/A' }}</td>
                                <td>{{ $obligation->department->department_name ?? 'N/A' }}</td>
                                <td>{{ $obligation->owner->name ?? 'Unassigned' }}</td>
                                <td>{{ $obligation->expiry_date->format('M d, Y') }}</td>
                                <td>
                                    @if($remaining < 0)
                                        <span class="text-danger fw-semibold">Expired {{ abs($remaining) }}d ago</span>
                                    @elseif($remaining === 0)
                                        <span class="text-danger fw-semibold">Today</span>
                                    @else
                                        {{ $remaining }} days
                                    @endif
                                </td>
                                <td><span class="badge text-bg-secondary">{{ ucwords(str_replace('_', ' ', $obligation->status)) }}</span></td>
                                <td>
                                    <x-badge :variant="\App\Support\StatusBadge::priorityVariant($obligation->priority)">
                                        {{ \App\Support\StatusBadge::label($obligation->priority) }}
                                    </x-badge>
                                </td>
                                <td>
                                    <x-badge :variant="\App\Support\StatusBadge::priorityVariant($obligation->risk_level)">
                                        {{ ucfirst($obligation->risk_level) }}
                                    </x-badge>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-icon-btn :href="route('obligations.show', $obligation)" icon="eye" label="View" />
                                        <x-icon-btn :href="route('obligations.edit', $obligation)" icon="pencil" label="Edit" />
                                        <form action="{{ route('obligations.destroy', $obligation) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                                    data-confirm="Delete obligation "{{ $obligation->obligation_no }}"? This cannot be undone."
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

            @if ($obligations->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$obligations" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="file-earmark-text" title="No obligations found" description="Get started by creating a new obligation.">
                    <x-btn :href="route('obligations.create')" icon="plus-lg" size="sm">New Obligation</x-btn>
                </x-empty-state>
            </div>
        @endif
    </div>
@endsection
