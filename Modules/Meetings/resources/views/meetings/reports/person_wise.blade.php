@extends('layouts.app')

@section('title', 'Person-wise Accountability')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => 'Reports', 'url' => route('meetings.reports.index')],
        ['label' => 'Person-wise Accountability'],
    ];
@endphp

@section('content')
<x-page-header title="Person-wise Accountability" subtitle="Action item accountability by person." />

<div class="card">
    @if($report->count())
        <div class="table-responsive">
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
                        <td><span class="badge {{ $row->overdue > 0 ? 'text-bg-danger' : 'text-bg-secondary' }}">{{ $row->overdue }}</span></td>
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
