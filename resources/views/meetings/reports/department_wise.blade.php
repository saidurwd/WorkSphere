@extends('layouts.app')

@section('title', 'Department Performance')

@section('breadcrumb', [
    ['label' => 'Dashboard', 'url' => {{ route('dashboard.index') }}],
    ['label' => 'Meetings', 'url' => {{ route('meetings.index') }}],
    ['label' => 'Reports', 'url' => {{ route('meetings.reports.index') }}],
    ['label' => 'Department Performance'],
])

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Department Performance</h1>
            <p class="page-description">Action item performance by department.</p>
        </div>
    </div>
</div>

<div class="card">
    @if($report->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Total</th>
                        <th>Completed</th>
                        <th>Pending</th>
                        <th>Overdue</th>
                        <th>Completion %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report as $row)
                    <tr>
                        <td>{{ $row->assignedDepartment->department_name ?? 'N/A' }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ $row->completed }}</td>
                        <td>{{ $row->pending }}</td>
                        <td><span class="badge {{ $row->overdue > 0 ? 'badge-danger' : 'badge-secondary' }}">{{ $row->overdue }}</span></td>
                        <td>
                            @php $completion = $row->total > 0 ? round(($row->completed / $row->total) * 100) : 0; @endphp
                            <span class="badge {{ $completion >= 80 ? 'badge-success' : ($completion >= 50 ? 'badge-warning' : 'badge-danger') }}">{{ $completion }}%</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <p style="margin: 0; color: var(--muted-foreground);">No department performance data available.</p>
        </div>
    @endif
</div>
@endsection
