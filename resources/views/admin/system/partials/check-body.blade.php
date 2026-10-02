{{--
    The body of one health card.

    A partial rather than a generic key/value dump because each check has
    DIFFERENT things worth showing. A pending migration is a list of names to
    deploy; a missing extension is a list of packages to install; the queue is a
    count and an age. Rendering all three as `key: value` pairs hides exactly the
    part an operator acts on.

    TWO BLADE TRAPS, both of which produce a parse error pointing at a variable
    name that looks perfectly valid, and neither of which names the real cause:

    1. This file must not BEGIN with a PHP open tag. Blade's compiler short-circuits
       on a leading one, treats the file as plain PHP, and leaves every directive
       uncompiled.
    2. A Blade comment must not contain the literal text of a directive. Blade
       extracts verbatim and script blocks BEFORE it extracts comments, so a
       directive named inside a comment is matched for real and swallows every line
       up to the next closing tag. This file originally lost its entire first half
       that way.

    @var array<string, mixed> $check
--}}
@php
    $statuses = ['pass' => 'success', 'warn' => 'warning', 'fail' => 'danger'];
    $statusVariant = $statuses[$check['status']] ?? 'secondary';

    // Fields already shown as the card's headline, so they are not repeated.
    $headline = ['status', 'message', 'problems', 'missing', 'unwritable', 'pending'];
@endphp

@switch($name)
    @case('migrations')
        <dl class="row mb-0 small">
            <dt class="col-7 text-body-secondary">Applied</dt>
            <dd class="col-5 mb-1">{{ number_format((int) ($check['applied'] ?? 0)) }}</dd>

            <dt class="col-7 text-body-secondary">Pending</dt>
            <dd class="col-5 mb-1">
                <x-badge :variant="($check['pending_count'] ?? 0) > 0 ? 'warning' : 'success'">
                    {{ (int) ($check['pending_count'] ?? 0) }}
                </x-badge>
            </dd>
        </dl>

        @if (! empty($check['pending']))
            {{-- Names, not just a count: "3 pending" does not say whether the NEXT
                 deploy is the one that applies them. --}}
            <div class="mt-2">
                <div class="small fw-semibold mb-1">Awaiting migration</div>
                <ul class="list-unstyled small mb-0 font-monospace">
                    @foreach ($check['pending'] as $migration)
                        <li class="text-truncate" title="{{ $migration }}">
                            <i class="bi bi-clock-history text-warning me-1"></i>{{ $migration }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="small text-body-secondary mt-2">
                <i class="bi bi-check-lg me-1"></i>Schema matches the codebase.
            </div>
        @endif
        @break

    @case('extensions')
        <div class="small">
            <div class="mb-2">
                <x-badge :variant="$statusVariant">
                    {{ (int) ($check['required_loaded'] ?? 0) }} / {{ (int) ($check['required_total'] ?? 0) }} required
                </x-badge>
            </div>

            @if (! empty($check['missing']))
                <div class="fw-semibold text-danger mb-1">Missing</div>
                <ul class="list-unstyled mb-0 font-monospace">
                    @foreach ($check['missing'] as $extension)
                        <li><i class="bi bi-x-circle-fill text-danger me-1"></i>ext-{{ $extension }}</li>
                    @endforeach
                </ul>
            @else
                <div class="text-body-secondary">
                    <i class="bi bi-check-lg me-1"></i>Every required extension is loaded.
                </div>
            @endif
        </div>

        @if (! empty($check['optional']))
            <div class="mt-2 pt-2 border-top">
                <div class="small fw-semibold mb-1">Optional</div>
                <div class="small text-body-secondary font-monospace">
                    @foreach ($check['optional'] as $extension => $state)
                        <span class="me-2">
                            {{ $extension }}
                            <i class="bi bi-{{ $state === 'loaded' ? 'check-lg text-success' : 'dash-lg' }}"></i>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
        @break

    @case('queue')
        <dl class="row mb-0 small">
            <dt class="col-7 text-body-secondary">Connection</dt>
            <dd class="col-5 mb-1"><code>{{ $check['connection'] ?? '—' }}</code></dd>

            <dt class="col-7 text-body-secondary">Pending</dt>
            <dd class="col-5 mb-1">
                <x-badge :variant="($check['pending'] ?? 0) > 0 ? 'info' : 'secondary'">
                    {{ number_format((int) ($check['pending'] ?? 0)) }}
                </x-badge>
            </dd>

            <dt class="col-7 text-body-secondary">Failed</dt>
            <dd class="col-5 mb-1">
                <x-badge :variant="($check['failed'] ?? 0) > 0 ? 'danger' : 'secondary'">
                    {{ number_format((int) ($check['failed'] ?? 0)) }}
                </x-badge>
            </dd>

            <dt class="col-7 text-body-secondary">Oldest waiting</dt>
            <dd class="col-5 mb-1">
                @if (($check['oldest_pending_minutes'] ?? null) === null)
                    <span class="text-body-secondary">—</span>
                @else
                    <x-badge :variant="($check['oldest_pending_minutes'] ?? 0) >= 60 ? 'warning' : 'secondary'">
                        {{ $check['oldest_pending_minutes'] }} min
                    </x-badge>
                @endif
            </dd>
        </dl>

        @if (($check['failed'] ?? 0) > 0)
            <a href="{{ route('admin.system.queue.index') }}" class="btn btn-sm btn-outline-danger mt-2">
                Review failed jobs
            </a>
        @endif
        @break

    @case('scheduler')
        <dl class="row mb-0 small">
            <dt class="col-6 text-body-secondary">Scheduled</dt>
            <dd class="col-6 mb-1">{{ (int) ($check['events'] ?? 0) }} events</dd>

            <dt class="col-6 text-body-secondary">Timezone</dt>
            <dd class="col-6 mb-1"><code>{{ $check['timezone'] ?? '—' }}</code></dd>

            <dt class="col-6 text-body-secondary">Next run</dt>
            <dd class="col-6 mb-1">
                @if (! empty($check['next_run']))
                    <time datetime="{{ $check['next_run'] }}">{{ $check['next_run'] }}</time>
                @else
                    <span class="text-body-secondary">Nothing scheduled</span>
                @endif
            </dd>
        </dl>

        @if (($check['unguarded_events'] ?? 0) > 0)
            <div class="small text-warning mt-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                {{ $check['unguarded_events'] }} event(s) lack overlap or single-server protection.
            </div>
        @endif

        <a href="{{ route('admin.system.schedule.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
            View schedule
        </a>
        @break

    @case('configuration')
        @if (! empty($check['problems']))
            <ul class="list-unstyled small mb-2">
                @foreach ($check['problems'] as $problem)
                    <li class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $problem }}</li>
                @endforeach
            </ul>
        @else
            <div class="small text-body-secondary mb-2">
                <i class="bi bi-check-lg me-1"></i>No configuration problems detected.
            </div>
        @endif

        <dl class="row mb-0 small">
            <dt class="col-5 text-body-secondary">Environment</dt>
            <dd class="col-7 mb-1">{{ $check['environment'] ?? '—' }}</dd>

            <dt class="col-5 text-body-secondary">Debug</dt>
            <dd class="col-7 mb-1">
                <x-badge :variant="($check['debug'] ?? false) ? 'warning' : 'secondary'">
                    {{ ($check['debug'] ?? false) ? 'On' : 'Off' }}
                </x-badge>
            </dd>

            <dt class="col-5 text-body-secondary">App key</dt>
            <dd class="col-7 mb-1">
                {{-- Never the key itself: this screen is exposed more widely than the
                     application, and a monitoring system is not the place a key belongs. --}}
                <x-badge :variant="($check['app_key'] ?? '') === 'missing' ? 'danger' : 'success'">
                    {{ ucfirst((string) ($check['app_key'] ?? 'unknown')) }}
                </x-badge>
            </dd>

            <dt class="col-5 text-body-secondary">Timezone</dt>
            <dd class="col-7 mb-1">{{ $check['timezone'] ?? '—' }}</dd>

            <dt class="col-5 text-body-secondary">Drivers</dt>
            <dd class="col-7 mb-1 font-monospace">
                @foreach ((array) ($check['drivers'] ?? []) as $driver => $value)
                    <span class="d-block">{{ $driver }}: {{ $value }}</span>
                @endforeach
            </dd>
        </dl>
        @break

    @case('schema')
        <dl class="row mb-0 small">
            <dt class="col-7 text-body-secondary">Models compared</dt>
            <dd class="col-5 mb-1">{{ number_format((int) ($check['models_checked'] ?? 0)) }}</dd>

            <dt class="col-7 text-body-secondary">Mismatches</dt>
            <dd class="col-5 mb-1">
                <x-badge :variant="($check['mismatches'] ?? 0) === 0 ? 'success' : 'danger'">
                    {{ (int) ($check['mismatches'] ?? 0) }}
                </x-badge>
            </dd>
        </dl>

        @if (! empty($check['detail']))
            <div class="mt-2">
                <div class="small fw-semibold mb-1">Declared but absent from the database</div>
                <ul class="list-unstyled small mb-0 font-monospace">
                    @foreach ($check['detail'] as $line)
                        <li class="text-danger text-truncate" title="{{ $line }}">
                            <i class="bi bi-x-octagon-fill me-1"></i>{{ $line }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="small text-warning mt-2">
                <i class="bi bi-wrench-adjustable me-1"></i>
                A column added by editing a migration that had already run here will never appear.
                Write a NEW migration.
            </div>
        @else
            <div class="small text-body-secondary mt-2">
                <i class="bi bi-check-lg me-1"></i>Every column the models declare exists in this database.
            </div>
        @endif

        <a href="{{ url('/artisan/doctor:schema') }}" class="btn btn-sm btn-outline-secondary mt-2"
           onclick="return false;" aria-label="Run: php artisan doctor:schema" title="Run: php artisan doctor:schema">
            Run <code>doctor:schema</code>
        </a>
        @break

    @case('storage')
        <dl class="row mb-0 small">
            @foreach ((array) ($check['paths'] ?? []) as $label => $path)
                <dt class="col-5 text-body-secondary text-capitalize">{{ $label }}</dt>
                <dd class="col-7 mb-1 font-monospace text-truncate" title="{{ $path }}">
                    @if (in_array($label, (array) ($check['unwritable'] ?? []), true))
                        <span class="text-danger">
                            <i class="bi bi-x-circle-fill me-1"></i>{{ $path }}
                        </span>
                    @else
                        <span class="text-success"><i class="bi bi-check-lg me-1"></i>{{ $path }}</span>
                    @endif
                </dd>
            @endforeach
        </dl>
        @break

    @default
        {{-- Everything else: a definition list of the scalar facts, skipping the
             fields the check already renders as its own headline. --}}
        <dl class="row mb-0 small">
            @foreach ($check as $key => $value)
                @continue(in_array($key, $headline, true))
                <dt class="col-6 text-body-secondary text-capitalize">
                    {{ str_replace('_', ' ', $key) }}
                </dt>
                <dd class="col-6 mb-1 text-break">
                    @if (is_bool($value))
                        <i class="bi bi-{{ $value ? 'check-lg text-success' : 'dash-lg text-body-secondary' }}"></i>
                    @elseif (is_array($value))
                        @if ($value === [])
                            <span class="text-body-secondary">—</span>
                        @else
                            @foreach ($value as $k => $v)
                                <span class="me-2">{{ $k }}: {{ is_bool($v) ? ($v ? 'yes' : 'no') : $v }}</span>
                            @endforeach
                        @endif
                    @else
                        {{ $value === null ? '—' : $value }}
                    @endif
                </dd>
            @endforeach
        </dl>
@endswitch
