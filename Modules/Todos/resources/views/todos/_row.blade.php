{{-- One To-Do row. Kept as a partial so the list, the inbox and the calendar can
     all render the same row without drifting apart.

     Every user-supplied value goes through {{ }} — never {!! !!}. --}}
@php
    $isOverdue = $todo->due_date !== null
        && $todo->due_date->isPast()
        && \App\Enums\WorkItemStatus::from($todo->status->value)->isOpen();
@endphp

<tr>
    <td>
        <input class="form-check-input" type="checkbox" value="{{ $todo->id }}" name="ids[]"
               data-todo-select aria-label="Select {{ $todo->title }}">
    </td>
    <td>
        <a href="{{ route('todos.show', $todo) }}" class="fw-semibold text-decoration-none">
            {{ $todo->title }}
        </a>

        @if ($todo->waiting_on)
            <x-badge variant="warning" icon="hourglass-split">Waiting on {{ $todo->waiting_on }}</x-badge>
        @endif

        @if ($todo->isRecurring())
            <x-badge variant="info" icon="repeat">Repeating</x-badge>
        @endif

        <div class="small text-body-secondary">
            {{ $todo->comments_count ?? 0 }} {{ \Illuminate\Support\Str::plural('comment', $todo->comments_count ?? 0) }}
            @if (($todo->checklist_items_count ?? 0) > 0)
                · {{ $todo->checklist_items_count }} checklist {{ \Illuminate\Support\Str::plural('item', $todo->checklist_items_count) }}
            @endif
        </div>
    </td>
    <td>
        @if ($todo->assignee)
            <x-user-cell :name="$todo->assignee->name" :email="$todo->assignee->email" :size="32" />
        @else
            <span class="text-body-secondary">Unassigned</span>
        @endif
    </td>
    <td>
        <x-badge :variant="\App\Support\StatusBadge::priorityVariant($todo->priority)">
            {{ \App\Support\StatusBadge::label($todo->priority) }}
        </x-badge>
    </td>
    <td>
        <x-badge :variant="\App\Support\StatusBadge::variant($todo->status)">
            {{ \App\Support\StatusBadge::label($todo->status) }}
        </x-badge>
    </td>
    <td class="text-nowrap">
        @if ($todo->due_date)
            <span @class(['text-danger fw-semibold' => $isOverdue])>
                {{ $todo->due_date->format('M d, Y') }}
            </span>
            @if ($isOverdue)
                <span class="visually-hidden">(overdue)</span>
            @endif
        @else
            <span class="text-body-secondary">No date</span>
        @endif
    </td>
    <td>
        <div class="d-flex justify-content-end gap-1">
            @can('update', $todo)
                @if (\App\Enums\WorkItemStatus::from($todo->status->value)->isOpen())
                    <form action="{{ route('todos.complete', $todo) }}" method="POST" class="d-inline">
                        @csrf
                        <x-icon-btn type="submit" icon="check2" label="Complete {{ $todo->title }}" variant="outline-success" />
                    </form>
                @else
                    <form action="{{ route('todos.reopen', $todo) }}" method="POST" class="d-inline">
                        @csrf
                        <x-icon-btn type="submit" icon="arrow-counterclockwise" label="Reopen {{ $todo->title }}"
                                    variant="outline-warning" />
                    </form>
                @endif

                <x-icon-btn :href="route('todos.edit', $todo)" icon="pencil" label="Edit {{ $todo->title }}" />
            @endcan

            <x-icon-btn :href="route('todos.show', $todo)" icon="eye" label="Open {{ $todo->title }}" />
        </div>
    </td>
</tr>
