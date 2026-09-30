@extends('layouts.app')

@section('title', 'Vendors')

@section('breadcrumb')
<a href="{{ route('dashboard.index') }}">Dashboard</a>
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
        <a href="{{ route('obligations.vendors.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Vendor
        </a>
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
                        <th style="text-align: right;">Actions</th>
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
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('obligations.vendors') }}" class="action-btn" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('obligations.vendors.edit', $vendor) }}" class="action-btn" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('obligations.vendors.destroy', $vendor) }}" method="POST" class="d-inline" data-confirm="Delete this vendor?" data-confirm-button="Delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-btn-danger" title="Delete">
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
