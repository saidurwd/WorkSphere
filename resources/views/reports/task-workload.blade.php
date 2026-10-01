@extends('layouts.app')

@section('title', 'Team Workload')

@section('header-actions')
    <x-btn :href="route('reports.workload.export')" icon="filetype-csv">Export CSV</x-btn>
@endsection

@section('content')
    <x-page-header title="Team Workload" subtitle="Open work carried by each person." icon="people" />

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Assignees</h3></div>

        @if ($rows->isEmpty())
            <div class="card-body">
                <x-empty-state icon="people" title="No assigned tasks" description="Nobody is carrying work yet." />
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Assignee</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col" class="text-end">Completed</th>
                            <th scope="col" class="text-end">Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row->assignee }}</td>
                                <td class="text-end">{{ (int) $row->total }}</td>
                                <td class="text-end">{{ (int) $row->completed }}</td>
                                <td class="text-end">
                                    @if ((int) $row->overdue > 0)
                                        <span class="text-danger fw-semibold">{{ (int) $row->overdue }}</span>
                                    @else
                                        0
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
