@extends('layouts.app')

@section('title', 'Obligations Reports')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Obligations', 'url' => route('obligations.dashboard')],
        ['label' => 'Reports'],
    ];
@endphp

@section('content')
<x-page-header title="Obligations Reports" subtitle="Analytics and performance reports." />

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Expiry Report (Next 90 Days)</h3>
    </div>
    <div class="card-body">
        @if($expiryReport->count())
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Type</th>
                            <th scope="col">Total Expiring</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiryReport as $row)
                        <tr>
                            <td>{{ $row->type_name }}</td>
                            <td><strong>{{ $row->total }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="color: var(--muted-foreground);">No obligations expiring in the next 90 days.</p>
        @endif
    </div>
</div>

<div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
    <div class="col card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Department Report</h3>
        </div>
        <div class="card-body">
            @if($departmentStats->count())
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Department</th>
                                <th scope="col">Total</th>
                                <th scope="col">Expired</th>
                                <th scope="col">Critical</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentStats as $row)
                            <tr>
                                <td>{{ $row->department_name }}</td>
                                <td>{{ $row->total }}</td>
                                <td>{{ $row->expired }}</td>
                                <td>{{ $row->critical }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color: var(--muted-foreground);">No data available.</p>
            @endif
        </div>
    </div>

    <div class="col card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Vendor Report</h3>
        </div>
        <div class="card-body">
            @if($vendorStats->count())
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Vendor</th>
                                <th scope="col">Total Obligations</th>
                                <th scope="col">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendorStats as $row)
                            <tr>
                                <td>{{ $row->vendor_name }}</td>
                                <td>{{ $row->total }}</td>
                                <td>{{ $row->total_cost ? number_format($row->total_cost, 2).' BDT' : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color: var(--muted-foreground);">No data available.</p>
            @endif
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Renewal Performance</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
            <div style="text-align: center;">
                <div style="font-size: 1.75rem; font-weight: 700;">{{ $renewalStats->on_time ?? 0 }}</div>
                <div style="color: var(--muted-foreground);">On-time Renewals</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.75rem; font-weight: 700;">{{ $renewalStats->late ?? 0 }}</div>
                <div style="color: var(--muted-foreground);">Late Renewals</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.75rem; font-weight: 700;">{{ $renewalStats->expired_count ?? 0 }}</div>
                <div style="color: var(--muted-foreground);">Expired</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 1.75rem; font-weight: 700;">{{ $renewalStats->avg_lead_time ? round($renewalStats->avg_lead_time) : 0 }}</div>
                <div style="color: var(--muted-foreground);">Avg Lead Time (days)</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Risk Report</h3>
    </div>
    <div class="card-body">
        @if($riskReport->count())
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Obligation No.</th>
                            <th scope="col">Title</th>
                            <th scope="col">Expiry Date</th>
                            <th scope="col">Risk</th>
                            <th scope="col">Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riskReport as $obligation)
                        @php
                            $riskBadge = match ($obligation->risk_level) {
                                'critical' => 'text-bg-danger',
                                'high' => 'text-bg-warning',
                                'medium' => 'text-bg-primary',
                                'low' => 'bg-secondary text-dark',
                            };
                        @endphp
                        <tr>
                            <td><a href="{{ route('obligations.show', $obligation) }}" style="text-decoration: none; color: inherit;">{{ $obligation->obligation_no }}</a></td>
                            <td>{{ $obligation->title }}</td>
                            <td>{{ $obligation->expiry_date->format('M d, Y') }}</td>
                            <td><span class="badge {{ $riskBadge }}">{{ ucfirst($obligation->risk_level) }}</span></td>
                            <td><x-badge variant="secondary">{{ ucfirst($obligation->priority) }}</x-badge></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="color: var(--muted-foreground);">No high-risk obligations.</p>
        @endif
    </div>
</div>
@endsection
