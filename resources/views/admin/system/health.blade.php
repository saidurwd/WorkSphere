@extends('layouts.app')

@section('title', 'System Health')

@section('content')
    <x-page-header
        title="System Health"
        subtitle="Live status of the application, its dependencies and its background work."
        icon="heart-pulse">
        {{-- Auto-refresh, because a health page that must be reloaded by hand is a
             health page nobody checks during an incident. The interval is a
             meta tag rather than a poll so it survives a slow response, and the
             value is clamped to something a human can still read. --}}
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="autoRefresh"
                   {{ $refresh > 0 ? 'checked' : '' }}
                   data-auto-refresh-seconds="{{ $refresh }}">
            <label class="form-check-label small" for="autoRefresh">
                Auto-refresh every {{ $refresh > 0 ? $refresh.'s' : 'off' }}
            </label>
        </div>

        <a href="{{ route('livez') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-broadcast me-1"></i>Live probe
        </a>
        <a href="{{ route('readyz') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-router me-1"></i>Ready probe
        </a>
    </x-page-header>

    <div class="alert alert-{{ $ok ? 'success' : 'danger' }} d-flex align-items-center gap-3" role="status">
        <i class="bi {{ $ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }} fs-4"></i>
        <div>
            <div class="fw-semibold">
                {{ $ok ? 'All checks passing' : count($failing).' check(s) failing' }}
            </div>
            <div class="small">
                {{ $report['application']['environment'] }} ·
                <time datetime="{{ $report['application']['time'] }}">{{ $report['application']['time'] }}</time>
            </div>
        </div>
    </div>

    @if ($warning)
        <div class="alert alert-warning" role="status">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>{{ count($warning) }} check(s) need attention.</strong>
            A queue with work in it is a queue doing its job; a backlog older than an
            hour means no worker is draining it.
        </div>
    @endif

    <div class="row g-3">
        @foreach ($report['checks'] as $name => $check)
            @php
                $variant = match ($check['status']) {
                    'pass' => 'success',
                    'warn' => 'warning',
                    default => 'danger',
                };
                $icon = match ($check['status']) {
                    'pass' => 'check-circle-fill',
                    'warn' => 'exclamation-triangle-fill',
                    default => 'x-circle-fill',
                };
            @endphp

            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border-{{ $variant }}">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h2 class="card-title mb-0 text-capitalize">{{ str_replace('_', ' ', $name) }}</h2>
                        {{-- The verdict is text as well as colour. A status page that
                             distinguishes "passing" from "failing" only by hue is
                             unreadable to a colour-blind operator and useless in a
                             monochrome incident report. --}}
                        <x-badge :variant="$variant">
                            <i class="bi bi-{{ $icon }} me-1"></i>{{ ucfirst($check['status']) }}
                        </x-badge>
                    </div>

                    <div class="card-body">
                        <dl class="row mb-0 small">
                            @foreach ($check as $key => $value)
                                @continue($key === 'status')
                                <dt class="col-6 text-body-secondary text-capitalize">
                                    {{ str_replace('_', ' ', $key) }}
                                </dt>
                                <dd class="col-6 mb-1 text-break">
                                    @if (is_bool($value))
                                        <i class="bi bi-{{ $value ? 'check-lg text-success' : 'dash-lg text-body-secondary' }}"></i>
                                    @elseif (is_array($value))
                                        @foreach ($value as $k => $v)
                                            {{ $k }}: {{ is_bool($v) ? ($v ? 'yes' : 'no') : $v }}@if (! $loop->last), @endif
                                        @endforeach
                                    @else
                                        {{ $value === null ? '—' : $value }}
                                    @endif
                                </dd>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">Probes</h2>
        </div>
        <div class="card-body">
            <p class="text-body-secondary small mb-3">
                Two endpoints, answering two different questions. Both are unauthenticated so an
                orchestrator can reach them; neither exposes a connection string, a credential or a
                row count.
            </p>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-1">
                            <code>GET {{ route('livez') }}</code>
                        </div>
                        <div class="small text-body-secondary mb-2">
                            Liveness. Answers "should this process be restarted?"
                        </div>
                        <div class="small">
                            <strong>503</strong> means restart. It does not touch the database: a
                            database blip must not cause every replica to be restarted and turn a
                            recoverable outage into a total one.
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-1">
                            <code>GET {{ route('readyz') }}</code>
                        </div>
                        <div class="small text-body-secondary mb-2">
                            Readiness. Answers "should traffic be sent here?"
                        </div>
                        <div class="small">
                            <strong>503</strong> means take this process out of rotation but leave it
                            running. It does check the database, the cache and the queue.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Polling the page rather than a dedicated JSON endpoint: the document above IS
        // the health document, so what an operator reads and what a monitor fetches are the
        // same data by construction.
        (function () {
            var box = document.getElementById('autoRefresh');
            var timer = null;

            if (! box) {
                return;
            }

            var seconds = parseInt(box.dataset.autoRefreshSeconds, 10);

            function schedule() {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }

                if (box.checked && seconds > 0) {
                    timer = window.setInterval(function () {
                        window.location.reload();
                    }, seconds * 1000);
                }
            }

            box.addEventListener('change', schedule);
            schedule();
        })();
    </script>
@endpush
