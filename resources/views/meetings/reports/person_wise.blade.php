@extends('tyro-dashboard::layouts.admin')

@section('title', 'Person-wise Accountability')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.reports.index') }}">Reports</a>
<span class="breadcrumb-separator">/</span>
<span>Person-wise Accountability</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Person-wise Accountability</h1>
            <p class="page-description">Action item accountability by person.</p>
        </div>
    </div>
</div>

<div class="card">
    @if($report->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Open</th>
                        <th>In Progress</th>
                        <th>Completed</th>
                        <th>Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report as $row)
                    <tr>
                        <td>{{ $row->assignedTo->name ?? 'N/A' }}</td>
                        <td>{{ $row->open }}</td>
                        <td>{{ $row->in_progress }}</td>
                        <td>{{ $row->completed }}</td>
                        <td><span class="badge {{ $row->overdue > 0 ? 'badge-danger' : 'badge-secondary' }}">{{ $row->overdue }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <p style="margin: 0; color: var(--muted-foreground);">No accountability data available.</p>
        </div>
    @endif
</div>
@endsection
