@extends('layouts.app')

@section('title', $template->name)

@section('content')
    <x-page-header :title="$template->name" icon="clipboard">
        <x-badge :variant="$template->is_active ? 'success' : 'secondary'">
            {{ $template->is_active ? 'Active' : 'Inactive' }}
        </x-badge>
        @can('meeting.manage_templates')
            <x-btn :href="route('meetings.templates.edit', $template)" variant="outline-secondary" icon="pencil">Edit</x-btn>
        @endcan
        <x-btn :href="route('meetings.templates.index')" variant="outline-secondary" icon="arrow-left">Back</x-btn>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title mb-0">Standing agenda</h3></div>

                @if ($template->agendaItems->isEmpty())
                    <div class="card-body">
                        <x-empty-state icon="list-task" title="No agenda items"
                                       description="A template with no agenda just saves the meeting defaults." />
                    </div>
                @else
                    <ol class="list-group list-group-flush list-group-numbered mb-0">
                        @foreach ($template->agendaItems as $item)
                            <li class="list-group-item">
                                <span class="fw-semibold">{{ $item->title }}</span>
                                @if ($item->description)
                                    <div class="small text-body-secondary">{{ $item->description }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            @if ($template->description)
                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Description</h3></div>
                    <div class="card-body">
                        <p class="mb-0" style="white-space: pre-line;">{{ $template->description }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-body-secondary"></i>Defaults
                    </h3>
                </div>
                <div class="card-body">
                    <x-detail-list :items="[
                        ['label' => 'Type', 'value' => $template->meetingType?->name ?? '—'],
                        ['label' => 'Priority', 'value' => \App\Support\StatusBadge::label($template->default_priority)],
                        ['label' => 'Duration', 'value' => $template->default_duration ? $template->default_duration.' min' : '—'],
                        ['label' => 'Location', 'value' => $template->default_location ?? '—'],
                    ]" />
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Schedule from this template</h3></div>
                <div class="card-body">
                    @if (! $template->is_active)
                        <x-alert type="warning"
                                 message="Activate the template before scheduling a meeting from it." />
                    @endif

                    <form action="{{ route('meetings.templates.schedule', $template) }}" method="POST"
                          class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label for="schedule-title" class="form-label">Meeting title</label>
                            <input id="schedule-title" type="text" name="title" class="form-control"
                                   value="{{ old('title', $template->name) }}" maxlength="255" required>
                        </div>

                        <div class="col-md-6">
                            <label for="schedule-date" class="form-label">Date</label>
                            <input id="schedule-date" type="date" name="meeting_date" class="form-control"
                                   value="{{ old('meeting_date', now()->addDay()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label for="schedule-start" class="form-label">Start</label>
                            <input id="schedule-start" type="time" name="start_time" class="form-control"
                                   value="{{ old('start_time', '10:00') }}" required>
                        </div>

                        <div class="col-md-3">
                            <label for="schedule-end" class="form-label">End</label>
                            <input id="schedule-end" type="time" name="end_time" class="form-control"
                                   value="{{ old('end_time', '11:00') }}" required>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100"
                                    @disabled(! $template->is_active)
                                    @if (! $template->is_active) disabled aria-disabled="true" @endif>
                                Schedule meeting
                            </button>
                        </div>

                        @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('meeting_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('end_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
