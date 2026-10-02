@extends('layouts.app')

@section('title', 'Renewals')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Obligations', 'url' => route('obligations.dashboard')],
        ['label' => 'Renewals'],
    ];
@endphp

@section('content')
<x-page-header title="Obligation Renewals" subtitle="Complete renewal history for all obligations." />

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.renewals') }}" method="GET" id="filter-form">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search by obligation number or title..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.renewals') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($renewals->count())
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Obligation</th>
                        <th scope="col">Renewal Date</th>
                        <th scope="col">Previous Expiry</th>
                        <th scope="col">New Expiry</th>
                        <th scope="col">Cost</th>
                        <th scope="col">Renewed By</th>
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
        <x-empty-state title="No renewals found" description="No renewal history available yet." />
    @endif
</div>
@endsection
