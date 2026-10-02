@extends('layouts.app')

@section('title', 'Queue')

@section('content')
    <x-page-header
        title="Queue &amp; Failed Jobs"
        subtitle="Background work in flight, and work that stopped."
        icon="list-task">
        <a href="{{ route('admin.system.schedule.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar-week me-1"></i>Schedule
        </a>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <x-stat title="Pending jobs" :value="array_sum(array_column($queues, 'pending'))" icon="hourglass-split" />
        </div>
        <div class="col-12 col-md-4">
            <x-stat title="Failed jobs" :value="count($failed)" icon="x-octagon" />
        </div>
        <div class="col-12 col-md-4">
            <x-stat title="Connection" :value="$connection" icon="plug" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title mb-0">Pending by queue</h2>
                </div>

                @if ($queues === [])
                    <div class="card-body">
                        <x-empty-state
                            icon="check2-circle"
                            title="Nothing queued"
                            description="No job is waiting on any queue." />
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <caption class="visually-hidden">Pending job depth per queue</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Queue</th>
                                    <th scope="col" class="text-center">Pending</th>
                                    <th scope="col" class="text-end">Oldest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($queues as $queue)
                                    @php(
                                        // The 60-minute line is the same threshold the
                                        // health check uses to warn, so the two
                                        // screens can never disagree about what
                                        // "old" means.
                                        $stale = $queue['oldest_minutes'] !== null && $queue['oldest_minutes'] >= 60
                                    )
                                    <tr>
                                        <th scope="row"><code>{{ $queue['queue'] }}</code></th>
                                        <td class="text-center">
                                            <x-badge :variant="$queue['pending'] > 0 ? 'info' : 'secondary'">
                                                {{ $queue['pending'] }}
                                            </x-badge>
                                        </td>
                                        <td class="text-end">
                                            @if ($queue['oldest_minutes'] === null)
                                                <span class="text-body-secondary small">—</span>
                                            @else
                                                <x-badge :variant="$stale ? 'warning' : 'secondary'">
                                                    {{ $queue['oldest_minutes'] }} min
                                                </x-badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h2 class="card-title mb-0">Failed jobs</h2>

                    @if ($failed->isNotEmpty())
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('admin.system.queue.retry-all') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary"
                                        data-confirm="Retry every failed job?">
                                    Retry all
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.system.queue.flush') }}">
                                @csrf
                                {{-- Destructive and irreversible: the payloads are
                                     discarded, not requeued. The confirm dialog is UX,
                                     not authorisation. --}}
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        data-confirm="Discard every failed job? This cannot be undone.">
                                    Discard all
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                @if ($failed->isEmpty())
                    <div class="card-body">
                        <x-empty-state
                            icon="check2-all"
                            title="No failed jobs"
                            description="Nothing has exhausted its retries." />
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <caption class="visually-hidden">Failed jobs</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Job</th>
                                    <th scope="col" class="text-center">Attempts</th>
                                    <th scope="col">Failed at</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($failed as $job)
                                    <tr>
                                        <th scope="row" class="fw-semibold">
                                            <code>{{ $job['display_name'] }}</code>
                                            <div class="fw-normal small text-body-secondary text-break">
                                                {{ $job['error'] }}
                                            </div>
                                            {{-- The payload is deliberately NOT shown.
                                                 It routinely contains notification
                                                 bodies, mail queue keys and model
                                                 identifiers; putting it on an
                                                 administration screen would expose
                                                 all of it to every administrator and
                                                 into every screenshot taken during an
                                                 incident. --}}
                                            <div class="fw-normal small text-body-secondary">
                                                <code class="text-truncate d-inline-block" style="max-width: 100%;"
                                                      title="{{ $job['id'] }}">{{ $job['id'] }}</code>
                                                @if ($job['queue'])
                                                    · queue <code>{{ $job['queue'] }}</code>
                                                @endif
                                            </div>
                                        </th>
                                        <td class="text-center">{{ $job['attempts'] }}</td>
                                        <td class="small">
                                            @if ($job['failed_at'])
                                                <time datetime="{{ $job['failed_at'] }}">{{ $job['failed_at'] }}</time>
                                            @else
                                                <span class="text-body-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group"
                                                 aria-label="Actions for this failed job">
                                                <form method="POST" action="{{ route('admin.system.queue.retry') }}">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $job['id'] }}">
                                                    <button type="submit" class="btn btn-outline-primary">Retry</button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.system.queue.forget') }}">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $job['id'] }}">
                                                    <button type="submit" class="btn btn-outline-danger"
                                                            data-confirm="Discard this failed job? This cannot be undone.">
                                                        Discard
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($batches)
        <div class="card mt-3">
            <div class="card-header">
                <h2 class="card-title mb-0">Recent batches</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <caption class="visually-hidden">Recent job batches</caption>
                    <thead>
                        <tr>
                            <th scope="col">Batch</th>
                            <th scope="col">Created</th>
                            <th scope="col" style="width: 30%;">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($batches as $batch)
                            <tr>
                                <th scope="row">
                                    {{ $batch['name'] }}
                                    <div class="fw-normal small text-body-secondary">
                                        {{ $batch['pending'] }} pending · {{ $batch['failed'] }} failed
                                    </div>
                                </th>
                                <td class="small">
                                    @if ($batch['created_at'])
                                        <time datetime="{{ $batch['created_at'] }}">{{ $batch['created_at'] }}</time>
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress" style="height: 8px;" role="presentation">
                                        <div class="progress-bar {{ $batch['failed'] > 0 ? 'bg-danger' : 'bg-success' }}"
                                             style="width: {{ $batch['progress'] }}%" aria-hidden="true"></div>
                                    </div>
                                    <span class="visually-hidden">{{ $batch['progress'] }} percent complete</span>
                                    <span class="small text-body-secondary">{{ $batch['progress'] }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
