@extends('layouts.app')

@section('title', 'To-Do Reports')

@section('content')
    <x-page-header title="Reports" subtitle="Completion, overdue and productivity." icon="bar-chart" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('todos.reports') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="report-from" class="form-label">From</label>
                    <input id="report-from" type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-4">
                    <label for="report-to" class="form-label">To</label>
                    <input id="report-to" type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('todos.reports.export', ['from' => $from, 'to' => $to]) }}"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat title="Open" :value="$summary->open" icon="inbox" variant="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat title="Overdue" :value="$summary->overdue" icon="exclamation-triangle" variant="danger" /></div>
        <div class="col-6 col-lg-3"><x-stat title="Completed" :value="$summary->completed" icon="check2-circle" variant="success" /></div>
        <div class="col-6 col-lg-3"><x-stat title="Unassigned" :value="$summary->unassigned" icon="person-dash" variant="warning" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-detail-card title="Completion by person" icon="person-check" class="mb-4">
                <x-progress-list :rows="$completionByOwner->map(fn ($row): array => [
                    'label' => $row->name,
                    'value' => (string) $row->completed_total,
                    'pct' => $completionByOwner->max('completed_total') > 0
                        ? (int) round($row->completed_total / $completionByOwner->max('completed_total') * 100)
                        : 0,
                ])->all()" />
            </x-detail-card>

            <x-detail-card title="Overdue by person" icon="exclamation-triangle" class="mb-4">
                <x-progress-list :rows="$overdueByOwner->map(fn ($row): array => [
                    'label' => $row->name,
                    'value' => (string) $row->overdue_total,
                    'pct' => $overdueByOwner->max('overdue_total') > 0
                        ? (int) round($row->overdue_total / $overdueByOwner->max('overdue_total') * 100)
                        : 0,
                ])->all()" />
            </x-detail-card>

            <x-detail-card title="Completion by department" icon="diagram-3" class="mb-4">
                <x-progress-list :rows="$completionByDepartment->map(fn ($row): array => [
                    'label' => 'Department #'.$row->department_id,
                    'value' => (string) $row->completed_total,
                    'pct' => $completionByDepartment->max('completed_total') > 0
                        ? (int) round($row->completed_total / $completionByDepartment->max('completed_total') * 100)
                        : 0,
                ])->all()" />
            </x-detail-card>
        </div>

        <div class="col-lg-6">
            <x-detail-card title="Workload distribution" icon="people" class="mb-4">
                <x-progress-list :rows="$workload->map(fn ($row): array => [
                    'label' => $row->name.' — '.$row->open_total.' open, '.$row->overdue_total.' overdue',
                    'value' => (string) $row->open_total,
                    'pct' => $workload->max('open_total') > 0
                        ? (int) round($row->open_total / $workload->max('open_total') * 100)
                        : 0,
                ])->all()" />
            </x-detail-card>

            <x-detail-card title="Personal productivity" icon="graph-up-arrow" class="mb-4">
                <x-progress-list :rows="$personalProductivity->map(fn ($row): array => [
                    'label' => $row->name.' — '.$row->created_total.' created, '.$row->completed_total.' done',
                    'value' => $row->completion_rate.'%',
                    'pct' => $row->completion_rate,
                ])->all()" />
            </x-detail-card>

            <x-detail-card title="Recurrence adherence" icon="repeat" class="mb-4">
                <x-progress-list :rows="$recurrenceAdherence->map(fn ($row): array => [
                    'label' => $row->name.' — '.$row->generated_total.' generated, '.$row->completed_total.' completed',
                    'value' => $row->adherence_rate.'%',
                    'pct' => $row->adherence_rate,
                ])->all()" />
            </x-detail-card>
        </div>

        <div class="col-12">
            <x-detail-card title="Overdue trend" icon="graph-down-arrow">
                <x-progress-list :rows="$overdueTrend->map(fn ($row): array => [
                    'label' => 'Week '.$row->period,
                    'value' => (string) $row->overdue_total,
                    'pct' => $overdueTrend->max('overdue_total') > 0
                        ? (int) round($row->overdue_total / $overdueTrend->max('overdue_total') * 100)
                        : 0,
                ])->all()" />
            </x-detail-card>
        </div>
    </div>
@endsection
