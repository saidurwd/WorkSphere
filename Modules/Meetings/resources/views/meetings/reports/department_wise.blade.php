@extends('layouts.app')

@section('title', 'Department Performance')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => 'Reports', 'url' => route('meetings.reports.index')],
        ['label' => 'Department Performance'],
    ];
@endphp

@section('content')
<x-page-header title="Department Performance" subtitle="Action item performance by department." />

<div class="card">
    @if($report->count())
        <div class="table-responsive">
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
                        <td><span class="badge {{ $row->overdue > 0 ? 'text-bg-danger' : 'bg-secondary text-dark' }}">{{ $row->overdue }}</span></td>
                        <td>
                            @php $completion = $row->total > 0 ? round(($row->completed / $row->total) * 100) : 0; @endphp
                            <span class="badge {{ $completion >= 80 ? 'text-bg-success' : ($completion >= 50 ? 'text-bg-warning' : 'text-bg-danger') }}">{{ $completion }}%</span>
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
