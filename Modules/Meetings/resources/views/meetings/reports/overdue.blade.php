@extends('layouts.app')

@section('title', 'Overdue Actions')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => 'Reports', 'url' => route('meetings.reports.index')],
        ['label' => 'Overdue Actions'],
    ];
@endphp

@section('content')
<x-page-header title="Overdue Actions" subtitle="Action items that are past due." />

<div class="card">
    @if($overdue->count())
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Action</th>
                        <th scope="col">Meeting</th>
                        <th scope="col">Assigned To</th>
                        <th scope="col">Department</th>
                        <th scope="col">Due Date</th>
                        <th scope="col">Days Overdue</th>
                        <th scope="col">Task</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overdue as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->meeting->title ?? 'N/A' }}</td>
                        <td>{{ $item->assignedTo->name ?? 'N/A' }}</td>
                        <td>{{ $item->assignedDepartment->department_name ?? 'N/A' }}</td>
                        <td>{{ $item->due_date->format('M d, Y') }}</td>
                        <td><x-badge variant="danger">{{ now()->startOfDay()->diffInDays($item->due_date) }} days</x-badge></td>
                        <td>
                            @if($item->task)
                            <a href="{{ route('tasks.show', $item->task) }}">{{ $item->task->task_no ?? 'Task #'.$item->task->id }}</a>
                            @else
                            <span style="color: var(--muted-foreground);">Not linked</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state title="No overdue actions" description="All action items are on track." />
    @endif
</div>
@endsection
