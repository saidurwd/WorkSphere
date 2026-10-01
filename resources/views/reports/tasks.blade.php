@extends('layouts.app')

@section('title', 'Task Reports')

@section('header-actions')
    <x-btn :href="route('reports.tasks.export', ['from' => $from, 'to' => $to])" icon="filetype-csv">Export CSV</x-btn>
@endsection

@section('content')
    <x-page-header title="Task Reports" subtitle="Completion by person and project." icon="bar-chart" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('reports.tasks') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="report-from" class="form-label">From</label>
                    <input id="report-from" type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-4">
                    <label for="report-to" class="form-label">To</label>
                    <input id="report-to" type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Completion by person</h3></div>

                @if ($byOwner->isEmpty())
                    <div class="card-body">
                        <x-empty-state icon="bar-chart" title="No tasks in this period"
                                       description="Widen the date range to see completion figures." />
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Assignee</th>
                                    <th scope="col" class="text-end">Created</th>
                                    <th scope="col" class="text-end">Completed</th>
                                    <th scope="col" class="text-end">Overdue</th>
                                    <th scope="col" class="text-end">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($byOwner as $row)
                                    @php
                                        $created = (int) $row->created_total;
                                        $rate = $created === 0 ? 0 : (int) round(((int) $row->completed_total / $created) * 100);
                                    @endphp
                                    <tr>
                                        <td>{{ $row->assignee }}</td>
                                        <td class="text-end">{{ $created }}</td>
                                        <td class="text-end">{{ (int) $row->completed_total }}</td>
                                        <td class="text-end">{{ (int) $row->overdue_total }}</td>
                                        <td class="text-end">{{ $rate }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Completion by project</h3></div>

                @if ($byProject->isEmpty())
                    <div class="card-body">
                        <x-empty-state icon="diagram-3" title="No project tasks in this period" />
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Project</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col" class="text-end">Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($byProject as $row)
                                    <tr>
                                        <td>{{ $row->project }}</td>
                                        <td class="text-end">{{ (int) $row->total }}</td>
                                        <td class="text-end">{{ (int) $row->completed }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Status and priority</h3></div>

                @if ($distribution->isEmpty())
                    <div class="card-body">
                        <x-empty-state icon="pie-chart" title="Nothing to chart" />
                    </div>
                @else
                    <x-detail-list :items="$distribution->map(fn ($row): array => [
                        'label' => ucwords(str_replace('_', ' ', (string) $row->status)).' · '.ucfirst((string) $row->priority),
                        'value' => (string) $row->total,
                    ])->values()->all()" />
                @endif
            </div>
        </div>
    </div>
@endsection
