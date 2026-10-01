@extends('layouts.app')

@section('title', 'My Work')

@section('content')
    <x-page-header title="My Work" subtitle="Tasks, To-Dos, action items and obligations in one place." icon="kanban" />

    {{-- Counts come from the same permission-gated rows that are rendered below.
         A user without `obligation.view` sees neither the obligation rows nor a
         count that reveals how many exist. --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat title="To-Dos" :value="$counts['todos']" icon="check2-square" variant="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat title="Tasks" :value="$counts['tasks']" icon="list-task" variant="info" /></div>
        <div class="col-6 col-lg-3"><x-stat title="Action items" :value="$counts['action_items']" icon="journal-check" variant="success" /></div>
        <div class="col-6 col-lg-3"><x-stat title="Obligations" :value="$counts['obligations']" icon="file-earmark-text" variant="warning" /></div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Everything assigned to you</h3>
        </div>

        @if ($workItems->isEmpty() && $obligations->isEmpty())
            <div class="card-body">
                <x-empty-state icon="inbox" title="Nothing waiting on you"
                               description="Work you are assigned to, watching, or that you created appears here.">
                    <a href="{{ route('todos.index') }}" class="btn btn-sm btn-outline-secondary">Go to To-Dos</a>
                </x-empty-state>
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach ($workItems as $row)
                    @php
                        $key = $row->source_type.':'.$row->source_id;
                        $url = $urls[$key] ?? null;
                        $overdue = $row->due_date !== null
                            && \Illuminate\Support\Carbon::parse($row->due_date)->isPast()
                            && ! in_array($row->status, ['completed', 'cancelled', 'archived'], true);
                    @endphp
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <x-badge variant="secondary">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $row->source_type)) }}</x-badge>

                        <div class="flex-grow-1 min-w-0">
                            {{-- Escaped: `title` comes from a user-authored record. --}}
                            <p class="mb-0 text-truncate fw-medium">
                                @if ($url)
                                    <a href="{{ $url }}" class="text-decoration-none">{{ $row->title }}</a>
                                @else
                                    {{ $row->title }}
                                @endif
                            </p>
                            <small class="text-body-secondary">
                                {{ \App\Support\StatusBadge::label($row->status) }}
                                @if ($row->due_date)
                                    · due {{ \Illuminate\Support\Carbon::parse($row->due_date)->format('M d, Y') }}
                                @endif
                            </small>
                        </div>

                        @if ($overdue)
                            <x-badge variant="danger" icon="exclamation-triangle">Overdue</x-badge>
                        @endif
                    </li>
                @endforeach

                @foreach ($obligations as $obligation)
                    <li class="list-group-item d-flex align-items-center gap-3">
                        <x-badge variant="warning">Obligation</x-badge>
                        <div class="flex-grow-1 min-w-0">
                            <p class="mb-0 text-truncate fw-medium">
                                <a href="{{ route('obligations.show', $obligation) }}" class="text-decoration-none">
                                    {{ $obligation->title }}
                                </a>
                            </p>
                            <small class="text-body-secondary">
                                {{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $obligation->status ?? '')) }}
                                @if ($obligation->expiry_date)
                                    · expires {{ $obligation->expiry_date->format('M d, Y') }}
                                @endif
                            </small>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
