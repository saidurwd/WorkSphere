@extends('layouts.app')

@section('title', 'To-Dos')

@section('header-actions')
    <x-btn :href="route('todos.calendar')" variant="outline-secondary" icon="calendar">Calendar</x-btn>
    <x-btn :href="route('todos.create')" icon="plus-lg">New To-Do</x-btn>
@endsection

@section('content')
    <x-page-header title="{{ ($inboxOnly ?? false) ? 'Inbox' : 'To-Dos' }}"
                   subtitle="{{ ($inboxOnly ?? false) ? 'Captured and waiting to be triaged.' : 'Everything you are party to.' }}"
                   icon="check2-square" />

    {{-- Quick capture: §8.3 calls this the single most important interaction in
         the module. A title-only input that creates a To-Do on Enter and stays on
         the page, so capturing is one gesture rather than a navigation. The
         response redirects back here, which is why it posts to `store` with
         `quick_capture`. --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('todos.store') }}" method="POST" id="quick-capture-form">
                @csrf
                <input type="hidden" name="quick_capture" value="1">

                <label for="quick-capture" class="form-label fw-semibold">
                    Capture a To-Do
                    <span class="text-body-secondary fw-normal">
                        — type a title and press Enter. <kbd>n</kbd> focuses this box.
                    </span>
                </label>

                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-plus-lg"></i></span>
                    <input id="quick-capture" type="text" name="title" class="form-control"
                           placeholder="What needs doing?" autocomplete="off"
                           maxlength="255" required data-quick-capture>
                    <button class="btn btn-primary" type="submit">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span data-submit-label>Add</span>
                    </button>
                </div>

                @error('title')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </form>
        </div>
    </div>

    @php
        $isFiltered = collect($filters)->filter(fn ($value) => $value !== null && $value !== false)->isNotEmpty();
    @endphp

    <div class="row g-3 mb-4" aria-live="polite">
        <div class="col-6 col-lg-3">
            <x-stat title="Open" :value="$summary->open" icon="inbox" variant="primary" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat title="Overdue" :value="$summary->overdue" icon="exclamation-triangle" variant="danger" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat title="Completed" :value="$summary->completed" icon="check2-circle" variant="success" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat title="Unassigned" :value="$summary->unassigned" icon="person-dash" variant="warning" />
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ ($inboxOnly ?? false) ? route('todos.inbox') : route('todos.index') }}" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-3">
                        <label for="filter-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="filter-search" type="search" name="search" class="form-control"
                                   placeholder="Search To-Dos…" value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>

                    @unless ($inboxOnly ?? false)
                        <div class="col-6 col-md-4 col-lg-2">
                            <label for="filter-status" class="form-label">Status</label>
                            <select id="filter-status" name="status" class="form-select">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status['value'] }}" @selected(($filters['status'] ?? '') === $status['value'])>
                                        {{ $status['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endunless

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-priority" class="form-label">Priority</label>
                        <select id="filter-priority" name="priority" class="form-select">
                            <option value="">All priorities</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>
                                    {{ $priority->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-assignee" class="form-label">Assignee</label>
                        <select id="filter-assignee" name="assignee_id" class="form-select">
                            <option value="">Anyone</option>
                            <option value="none" @selected(($filters['assignee_id'] ?? '') === 'none')>Unassigned</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(($filters['assignee_id'] ?? '') === (string) $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="filter-overdue" class="form-label">Quick filters</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter-overdue"
                                       name="overdue" value="1" @checked($filters['overdue'])>
                                <label class="form-check-label" for="filter-overdue">Overdue</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter-recurring"
                                       name="recurring" value="1" @checked($filters['recurring'])>
                                <label class="form-check-label" for="filter-recurring">Repeating</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i>Apply
                        </button>

                        @if ($isFiltered)
                            <a href="{{ ($inboxOnly ?? false) ? route('todos.inbox') : route('todos.index') }}"
                               class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk actions. The bar is sticky so it stays reachable while scrolling a
         long list; it is hidden until something is selected. Each submission is
         authorised per item server-side — the client-side check is a convenience,
         never the control. --}}
    <div id="bulk-bar" class="alert alert-secondary d-none sticky-top" style="z-index: 1020;" role="region"
         aria-label="Bulk actions">
        <form action="{{ route('todos.bulk') }}" method="POST" id="bulk-form" class="d-flex flex-wrap align-items-center gap-2">
            @csrf
            <input type="hidden" name="action" id="bulk-action" value="complete">
            <span class="fw-semibold"><span data-selected-count>0</span> selected</span>

            <select name="assignee_id" class="form-select form-select-sm w-auto d-none" id="bulk-assignee"
                    aria-label="Reassign to">
                <option value="">Unassign</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>

            <input type="text" name="tag" class="form-control form-control-sm w-auto d-none" id="bulk-tag"
                   placeholder="Tag name" aria-label="Tag name" maxlength="100">

            <button class="btn btn-sm btn-primary" type="submit"
                    data-confirm="Apply this action to the selected To-Dos? Anything you are not permitted to change will be skipped."
                    data-confirm-button="Apply">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                Apply
            </button>

            <button type="button" class="btn btn-sm btn-outline-secondary" data-bulk-cancel>Cancel</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                {{ ($inboxOnly ?? false) ? 'Inbox' : 'All To-Dos' }}
                <span class="text-body-secondary small">({{ $todos->total() }})</span>
            </h3>

            <div class="btn-group btn-group-sm" role="group" aria-label="Bulk action">
                <button type="button" class="btn btn-outline-primary" data-bulk="complete">Complete</button>
                <button type="button" class="btn btn-outline-primary" data-bulk="reassign">Reassign</button>
                <button type="button" class="btn btn-outline-primary" data-bulk="archive">Archive</button>
                <button type="button" class="btn btn-outline-primary" data-bulk="tag">Tag</button>
            </div>
        </div>

        @if ($todos->isEmpty())
            <div class="card-body">
                @if ($isFiltered)
                    {{-- Distinct copy per case: a filter that matches nothing is a
                         different situation from having no To-Dos at all, and the
                         copy should say so. --}}
                    <x-empty-state icon="funnel" title="No To-Dos match these filters"
                                   description="Try widening or clearing the filters above.">
                        <a href="{{ ($inboxOnly ?? false) ? route('todos.inbox') : route('todos.index') }}"
                           class="btn btn-sm btn-outline-secondary">Clear filters</a>
                    </x-empty-state>
                @elseif ($inboxOnly ?? false)
                    <x-empty-state icon="check2-all" title="Your inbox is empty"
                                   description="Anything you capture lands here first.">
                        <a href="{{ route('todos.index') }}" class="btn btn-sm btn-outline-secondary">See everything</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="inbox" title="Nothing captured yet"
                                   description="Use the box above to capture your first To-Do." />
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 2.5rem;">
                                <input class="form-check-input" type="checkbox" data-select-all
                                       aria-label="Select every To-Do on this page">
                            </th>
                            <th scope="col">Title</th>
                            <th scope="col">Assignee</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                            <th scope="col">Due</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($todos as $todo)
                            @include('todos._row', ['todo' => $todo])
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($todos->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$todos" />
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // Quick capture: Enter submits, then the field is cleared so a run of
        // captures does not need a click between each one.
        document.querySelector('[data-quick-capture]')?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || event.shiftKey) {
                return;
            }

            event.preventDefault();
            document.getElementById('quick-capture-form').requestSubmit();
        });

        // `n` focuses capture, unless the user is already typing somewhere.
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'n' || event.metaKey || event.ctrlKey || event.altKey) {
                return;
            }

            const active = document.activeElement;
            const typing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);

            if (typing) {
                return;
            }

            event.preventDefault();
            document.querySelector('[data-quick-capture]')?.focus();
        });

        // Button-level spinner on any submit, matching the data-confirm pattern.
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('[type="submit"]').forEach(function (button) {
                    button.querySelector('.spinner-border')?.classList.remove('d-none');
                    if (button.hasAttribute('data-submit-label')) {
                        button.disabled = true;
                    }
                });
            });
        });

        // Bulk selection.
        const bar = document.getElementById('bulk-bar');
        const counter = bar?.querySelector('[data-selected-count]');
        const actionInput = document.getElementById('bulk-action');
        const assigneeInput = document.getElementById('bulk-assignee');
        const tagInput = document.getElementById('bulk-tag');

        function selectedBoxes() {
            return Array.from(document.querySelectorAll('[data-todo-select]:checked'));
        }

        function refreshBulkBar() {
            const count = selectedBoxes().length;

            if (counter) {
                counter.textContent = String(count);
            }

            bar?.classList.toggle('d-none', count === 0);
        }

        document.addEventListener('change', function (event) {
            if (event.target.matches('[data-todo-select]')) {
                refreshBulkBar();
            }

            if (event.target.matches('[data-select-all]')) {
                document.querySelectorAll('[data-todo-select]').forEach(function (box) {
                    box.checked = event.target.checked;
                });

                refreshBulkBar();
            }
        });

        document.querySelectorAll('[data-bulk]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (selectedBoxes().length === 0) {
                    return;
                }

                const action = button.dataset.bulk;

                if (actionInput) {
                    actionInput.value = action;
                }

                // Only reveal the extra field the chosen action actually needs.
                assigneeInput?.classList.toggle('d-none', action !== 'reassign');
                tagInput?.classList.toggle('d-none', action !== 'tag');

                document.getElementById('bulk-form')?.scrollIntoView({ block: 'nearest' });
            });
        });

        document.querySelector('[data-bulk-cancel]')?.addEventListener('click', function () {
            document.querySelectorAll('[data-todo-select]').forEach(function (box) {
                box.checked = false;
            });

            const selectAll = document.querySelector('[data-select-all]');
            if (selectAll) {
                selectAll.checked = false;
            }

            refreshBulkBar();
        });
    </script>
@endpush
