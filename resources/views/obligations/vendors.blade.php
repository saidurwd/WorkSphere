@extends('tyro-dashboard::layouts.admin')

@section('title', 'Vendors')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.dashboard') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>Vendors</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Obligation Vendors</h1>
            <p class="page-description">Vendors associated with compliance obligations.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.vendors') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search vendors..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <select name="status" class="form-select" style="min-width: 150px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Statuses</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.vendors') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($vendors->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vendor Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td><strong>{{ $vendor->vendor_name }}</strong></td>
                        <td>{{ $vendor->contact_person ?? 'N/A' }}</td>
                        <td>{{ $vendor->email ?? 'N/A' }}</td>
                        <td>{{ $vendor->phone ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $vendor->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                {{ ucfirst($vendor->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($vendors->hasPages())
        <div class="pagination">
            {{ $vendors->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" />
            </svg>
            <h3 class="empty-state-title">No vendors found</h3>
            <p class="empty-state-description">No vendors have been added yet.</p>
        </div>
    @endif
</div>
@endsection
