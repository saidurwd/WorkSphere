@extends('layouts.app')

@section('title', 'Decision Register')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => 'Reports', 'url' => route('meetings.reports.index')],
        ['label' => 'Decision Register'],
    ];
@endphp

@section('content')
<x-page-header title="Decision Register" subtitle="Browse meeting decisions." />

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('meetings.reports.decisions') }}" method="GET">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search decisions..." value="{{ request('search') }}">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label">Type:</label>
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
        <div class="table-responsive">
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
