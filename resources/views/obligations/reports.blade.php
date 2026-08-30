@extends('tyro-dashboard::layouts.admin')

@section('title', 'Obligations Reports')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.dashboard') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>Reports</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Obligations Reports</h1>
            <p class="page-description">Analytics and performance reports.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Expiry Report (Next 90 Days)</h3>
    </div>
    <div class="card-body">
        @if($expiryReport->count())
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Total Expiring</th>
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
        @else
            <p style="color: var(--muted-foreground);">No obligations expiring in the next 90 days.</p>
        @endif
    </div>
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Department Report</h3>
        </div>
        <div class="card-body">
            @if($departmentStats->count())
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total</th>
                            <th>Expired</th>
                            <th>Critical</th>
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
            @else
                <p style="color: var(--muted-foreground);">No data available.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Vendor Report</h3>
        </div>
        <div class="card-body">
            @if($vendorStats->count())
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Total Obligations</th>
                            <th>Total Cost</th>
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
            <table class="table">
                <thead>
                    <tr>
                        <th>Obligation No.</th>
                        <th>Title</th>
                        <th>Expiry Date</th>
                        <th>Risk</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riskReport as $obligation)
                    @php
                        $riskBadge = match ($obligation->risk_level) {
                            'critical' => 'badge-danger',
                            'high' => 'badge-warning',
                            'medium' => 'badge-primary',
                            'low' => 'badge-secondary',
                        };
                    @endphp
                    <tr>
                        <td><a href="{{ route('obligations.show', $obligation) }}" style="text-decoration: none; color: inherit;">{{ $obligation->obligation_no }}</a></td>
                        <td>{{ $obligation->title }}</td>
                        <td>{{ $obligation->expiry_date->format('M d, Y') }}</td>
                        <td><span class="badge {{ $riskBadge }}">{{ ucfirst($obligation->risk_level) }}</span></td>
                        <td><span class="badge badge-secondary">{{ ucfirst($obligation->priority) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: var(--muted-foreground);">No high-risk obligations.</p>
        @endif
    </div>
</div>
@endsection
