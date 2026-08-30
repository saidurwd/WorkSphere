@extends('tyro-dashboard::layouts.admin')

@section('title', 'Renewals')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.dashboard') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>Renewals</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Obligation Renewals</h1>
            <p class="page-description">Complete renewal history for all obligations.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.renewals') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search by obligation number or title..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.renewals') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($renewals->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Obligation</th>
                        <th>Renewal Date</th>
                        <th>Previous Expiry</th>
                        <th>New Expiry</th>
                        <th>Cost</th>
                        <th>Renewed By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($renewals as $renewal)
                    <tr>
                        <td>
                            @if($renewal->obligation)
                                <a href="{{ route('obligations.show', $renewal->obligation) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $renewal->obligation->obligation_no }} - {{ $renewal->obligation->title }}
                                </a>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
                            @endif
                        </td>
                        <td>{{ $renewal->renewal_date->format('M d, Y') }}</td>
                        <td>{{ $renewal->previous_expiry_date->format('M d, Y') }}</td>
                        <td>{{ $renewal->new_expiry_date->format('M d, Y') }}</td>
                        <td>{{ $renewal->cost ? number_format($renewal->cost, 2).' '.$renewal->currency : 'N/A' }}</td>
                        <td>{{ $renewal->renewedBy->name ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($renewals->hasPages())
        <div class="pagination">
            {{ $renewals->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <h3 class="empty-state-title">No renewals found</h3>
            <p class="empty-state-description">No renewal history available yet.</p>
        </div>
    @endif
</div>
@endsection
