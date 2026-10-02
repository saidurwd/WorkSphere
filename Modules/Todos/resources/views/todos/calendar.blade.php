@extends('layouts.app')

@section('title', 'To-Do Calendar')

@section('content')
    <x-page-header title="Calendar" subtitle="Everything with a due date." icon="calendar">
        <x-btn :href="route('todos.index')" variant="outline-secondary" icon="arrow-left">Back to list</x-btn>
    </x-page-header>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="btn-group" role="group" aria-label="Move between periods">
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('todos.calendar', ['view' => $calendarView->value, 'date' => $previous]) }}"
                   aria-label="Previous {{ $calendarView->label() }}"><i class="bi bi-chevron-left"></i></a>
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('todos.calendar', ['view' => $calendarView->value, 'date' => $today]) }}">Today</a>
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('todos.calendar', ['view' => $calendarView->value, 'date' => $next]) }}"
                   aria-label="Next {{ $calendarView->label() }}"><i class="bi bi-chevron-right"></i></a>
            </div>

            <h2 class="h5 mb-0">
                {{ $calendarView === \App\Enums\CalendarView::Month
                    ? $anchor->format('F Y')
                    : $anchor->startOfWeek()->format('M d').' – '.$anchor->endOfWeek()->format('M d, Y') }}
            </h2>

            <div class="btn-group" role="group" aria-label="Calendar granularity">
                <a class="btn btn-sm {{ $calendarView === \App\Enums\CalendarView::Month ? 'btn-primary' : 'btn-outline-secondary' }}"
                   href="{{ route('todos.calendar', ['view' => 'month', 'date' => $anchor->toDateString()]) }}">Month</a>
                <a class="btn btn-sm {{ $calendarView === \App\Enums\CalendarView::Week ? 'btn-primary' : 'btn-outline-secondary' }}"
                   href="{{ route('todos.calendar', ['view' => 'week', 'date' => $anchor->toDateString()]) }}">Week</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{-- No `role="grid"`. That role promises arrow-key navigation between
                 cells, row semantics and `aria-rowcount`, none of which this
                 layout provides, so it made the content harder to reach than
                 leaving it unroled would. A labelled region conveys the same
                 grouping without promising interaction that is not there. --}}
            <div class="row g-2" role="region" aria-label="To-Dos by date">
                @foreach ($days as $day)
                    @php
                        $key = $day->toDateString();
                        $dayTodos = $byDate->get($key, collect());
                    @endphp
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="border rounded p-2 h-100
                                    {{ $key === $today ? 'border-primary' : '' }}"
                             aria-label="{{ $day->format('l, j F Y') }} — {{ $dayTodos->count() }} To-Dos">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span @class(['fw-semibold small' => $key !== $today, 'fw-bold small text-primary' => $key === $today])>
                                    {{ $day->format('D j') }}
                                </span>
                                @if ($dayTodos->isNotEmpty())
                                    <x-badge variant="secondary">{{ $dayTodos->count() }}</x-badge>
                                @endif
                            </div>

                            @forelse ($dayTodos->take(3) as $todo)
                                <a href="{{ route('todos.show', $todo) }}"
                                   class="d-block text-truncate small text-decoration-none text-body-{{ \App\Support\StatusBadge::variant($todo->status) }}"
                                   title="{{ $todo->title }}">
                                    {{ $todo->title }}
                                </a>
                            @empty
                                <span class="text-body-secondary small">&mdash;</span>
                            @endforelse

                            @if ($dayTodos->count() > 3)
                                <span class="text-body-secondary small">+{{ $dayTodos->count() - 3 }} more</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($byDate->isEmpty())
                <x-empty-state icon="calendar-x" title="Nothing due in this period"
                               description="Add a due date to a To-Do and it will appear here." />
            @endif
        </div>
    </div>
@endsection
