@extends('layouts.app')

@section('title', 'Search')

@section('content')
    <x-page-header title="Search" subtitle="Across To-Dos, tasks, meetings, obligations and projects." icon="search" />

    <div class="card mb-4">
        <div class="card-body">
            {{-- Debounced so a partial term is not searched on every keystroke.
                 The form still submits normally without JavaScript. --}}
            <form action="{{ route('search') }}" method="GET" role="search" id="search-form">
                <label for="search-term" class="form-label">Search for</label>

                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="search-term" type="search" name="q" class="form-control"
                           placeholder="Type at least two characters…" maxlength="120"
                           value="{{ $term }}" autocomplete="off"
                           aria-describedby="search-hint" data-search-input>
                    <button class="btn btn-primary" type="submit">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Search
                    </button>
                </div>

                <p id="search-hint" class="form-text">
                    Press <kbd>/</kbd> to focus. Results are limited to what you are permitted to see.
                </p>
            </form>
        </div>
    </div>

    @if ($term === '')
        <div class="card">
            <div class="card-body">
                <x-empty-state icon="search" title="Nothing searched yet"
                               description="Type a word or two above. Titles and descriptions are searched." />
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Filters</h3></div>
                    <div class="card-body">
                        @php $anyModule = ($filters['modules'] ?? []) !== []; @endphp

                        <p class="small fw-semibold text-body-secondary text-uppercase">Module</p>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="module-all"
                                   {{ old('modules', $filters['modules'] ?? []) === [] ? 'checked' : '' }}>
                            <label class="form-check-label" for="module-all">All modules</label>
                        </div>

                        @foreach ($entities as $key => $entity)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $key }}"
                                       id="module-{{ $key }}" form="search-form"
                                       data-module-filter
                                       @checked(in_array($key, $filters['modules'] ?? [], true) || $anyModule)>
                                <label class="form-check-label" for="module-{{ $key }}">
                                    {{ $entity->label }}
                                </label>
                            </div>
                        @endforeach

                        @if (($statusValues ?? []) !== [])
                            <p class="small fw-semibold text-body-secondary text-uppercase mt-3">Status</p>
                            <select name="status" class="form-select form-select-sm mb-3" form="search-form">
                                <option value="">Any status</option>
                                @foreach (array_unique(Arr::flatten($statusValues)) as $status)
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                        {{ ucwords(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <p class="small fw-semibold text-body-secondary text-uppercase">Owner</p>
                        <select name="owner" class="form-select form-select-sm mb-3" form="search-form">
                            <option value="">Anyone</option>
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->id }}" @selected((int) ($filters['owner'] ?? 0) === $owner->id)>
                                    {{ $owner->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="row g-2">
                            <div class="col-6">
                                <label for="search-from" class="form-label small">From</label>
                                <input id="search-from" type="date" name="from" class="form-control form-control-sm"
                                       form="search-form" value="{{ $filters['from'] ?? '' }}">
                            </div>
                            <div class="col-6">
                                <label for="search-to" class="form-label small">To</label>
                                <input id="search-to" type="date" name="to" class="form-control form-control-sm"
                                       form="search-form" value="{{ $filters['to'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-sm btn-primary w-100" form="search-form">Apply</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                {{-- aria-live so a screen reader announces the result count when a
                     debounced search completes. --}}
                <div class="d-flex justify-content-between align-items-center mb-3" aria-live="polite">
                    <h2 class="h5 mb-0">
                        @if ($total === 0)
                            No results
                        @else
                            {{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }} for
                            <span class="text-body-secondary">“{{ $term }}”</span>
                        @endif
                    </h2>
                </div>

                @forelse ($groups as $group)
                    @php $entity = $group['entity']; @endphp
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">{{ $entity->label }}</h3>
                            <span class="badge bg-secondary text-dark">{{ $group['count'] }}</span>
                        </div>

                        <ul class="list-group list-group-flush">
                            @foreach ($group['rows'] as $row)
                                @php $url = $entity->urlFor($row); @endphp
                                <li class="list-group-item">
                                    @if ($url)
                                        <a href="{{ $url }}" class="fw-semibold text-decoration-none">
                                            {{ $row->{$entity->titleColumn} }}
                                        </a>
                                    @else
                                        <span class="fw-semibold">{{ $row->{$entity->titleColumn} }}</span>
                                    @endif

                                    <div class="small text-body-secondary">
                                        @if ($entity->statusColumn && $row->{$entity->statusColumn} !== null)
                                            <x-badge :variant="\App\Support\StatusBadge::variant($row->{$entity->statusColumn})">
                                                {{ \App\Support\StatusBadge::label($row->{$entity->statusColumn}) }}
                                            </x-badge>
                                        @endif
                                        @if ($entity->dateColumn && $row->{$entity->dateColumn} !== null)
                                            <span class="ms-2">
                                                {{ \Illuminate\Support\Carbon::parse($row->{$entity->dateColumn})->format('M d, Y') }}
                                            </span>
                                        @endif
                                    </div>

                                    @php $excerpt = $entity->excerpt($row, $term); @endphp
                                    @if ($excerpt !== '')
                                        <div class="small mt-1">{!! $excerpt !!}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body">
                            {{-- Distinct copy from the "nothing searched" state: a
                                 search that ran and found nothing is a different
                                 situation and reads as a failure otherwise. --}}
                            <x-empty-state icon="search" title="Nothing matched “{{ $term }}”"
                                           description="Try a shorter term, or clear the filters. Only records you are permitted to see are searched.">
                                <a href="{{ route('search') }}" class="btn btn-sm btn-outline-secondary">Start over</a>
                            </x-empty-state>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        const input = document.querySelector('[data-search-input]');

        // Debounced auto-submit: a partial term should not be searched on every
        // keystroke. 350ms is long enough to finish a word and short enough that
        // the page still feels live.
        if (input) {
            let timer = null;

            input.addEventListener('input', function () {
                window.clearTimeout(timer);

                timer = window.setTimeout(function () {
                    if (input.value.trim().length >= 2) {
                        document.getElementById('search-form').requestSubmit();
                    }
                }, 350);
            });
        }

        // `/` focuses the box from anywhere, unless the user is already typing.
        // The navbar registers its own handler first; this one only runs when no
        // navbar box is present, so the two never fight over the shortcut.
        if (!document.querySelector('[data-search-box]')) {
            document.addEventListener('keydown', function (event) {
                if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
                    return;
                }

                const active = document.activeElement;
                if (active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName)) {
                    return;
                }

                event.preventDefault();
                input?.focus();
                input?.select();
            });
        }

        // "All modules" clears the per-module selection rather than adding to it.
        const all = document.getElementById('module-all');
        all?.addEventListener('change', function () {
            if (all.checked) {
                document.querySelectorAll('[data-module-filter]').forEach(function (box) {
                    box.checked = false;
                });
            }
        });
    </script>
@endpush
