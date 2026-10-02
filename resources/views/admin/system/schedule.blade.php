@extends('layouts.app')

@section('title', 'Schedule')

@section('content')
    <x-page-header
        title="Scheduled Tasks"
        subtitle="What the scheduler will run, when, and in which timezone."
        icon="calendar-week">
        <a href="{{ route('admin.system.queue.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-task me-1"></i>Queue
        </a>
    </x-page-header>

    <x-alert type="info">
        This list is read from the application's own schedule definition at request time, not from a
        record of what was scheduled. It therefore cannot drift: if an entry is not here, it does not
        run.
    </x-alert>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="fw-semibold">Scheduler timezone</div>
                <div class="small text-body-secondary">
                    Every entry is pinned to this zone. An entry without one would be evaluated in the
                    server's timezone, which is how a 09:00 reminder fires at 03:00.
                </div>
            </div>
            <code class="fs-5">{{ $timezone }}</code>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Scheduled tasks</caption>
                <thead>
                    <tr>
                        <th scope="col">Command</th>
                        <th scope="col">Frequency</th>
                        <th scope="col">Next run</th>
                        <th scope="col">Guards</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <th scope="row" class="fw-semibold">
                                <code>{{ $event['description'] }}</code>

                                @if ($event['missing_protections'] !== [])
                                    {{-- Shown rather than assumed. The absence of
                                         withoutOverlapping or onOneServer is invisible
                                         until the hour it matters, so it is stated here
                                         instead. --}}
                                    <div class="fw-normal mt-1">
                                        <x-badge variant="warning">
                                            Missing: {{ implode(', ', $event['missing_protections']) }}
                                        </x-badge>
                                    </div>
                                @endif
                            </th>

                            <td>
                                <code>{{ $event['expression'] }}</code>
                                <div class="small text-body-secondary">{{ $event['timezone'] }}</div>
                            </td>

                            <td>
                                @if ($event['next_run'])
                                    {{-- The machine-readable value is in `datetime`; the
                                         human one is adjacent and not interchangeable,
                                         because "in 4 minutes" is useless to a log. --}}
                                    <time datetime="{{ $event['next_run'] }}" title="{{ $event['next_run'] }}">
                                        {{ $event['next_run_human'] }}
                                    </time>
                                @else
                                    {{-- Null for a one-shot entry that has already run.
                                         Rendering "never" would be a claim; this is a
                                         fact. --}}
                                    <span class="text-body-secondary small">Will not run again</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <x-badge :variant="$event['without_overlapping'] ? 'success' : 'secondary'"
                                             title="withoutOverlapping">
                                        no-overlap
                                    </x-badge>
                                    <x-badge :variant="$event['on_one_server'] ? 'success' : 'secondary'"
                                             title="onOneServer">
                                        one-server
                                    </x-badge>
                                    <x-badge :variant="$event['has_timezone'] ? 'success' : 'secondary'"
                                             title="timezone">
                                        tz
                                    </x-badge>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
