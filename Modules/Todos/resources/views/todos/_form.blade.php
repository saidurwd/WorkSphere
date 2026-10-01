{{-- Shared create/edit form body.

     `status` is deliberately absent: a status change is a transition through the
     §3.2 graph, not a field write, and exposing it here would let the form
     bypass the transition rules entirely. --}}

@php
    $selected = fn (string $field, mixed $fallback): string => (string) old($field, $fallback ?? '');
@endphp

<x-form.input name="title" label="Title" :value="$todo->title" required maxlength="255" />

<x-form.textarea name="description" label="Description" :value="$todo->description"
                 rows="4" maxlength="5000" />

<div class="row g-3">
    <div class="col-md-6">
        <x-form.select name="priority" label="Priority" :value="$selected('priority', $todo->priority?->value ?? 'medium')">
            @foreach (\App\Enums\Priority::cases() as $priority)
                <option value="{{ $priority->value }}" @selected($selected('priority', $todo->priority?->value ?? 'medium') === $priority->value)>
                    {{ $priority->label() }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="col-md-6">
        <x-form.select name="visibility" label="Visibility" :value="$selected('visibility', $todo->visibility?->value ?? 'personal')">
            @foreach (\App\Enums\Visibility::cases() as $visibility)
                <option value="{{ $visibility->value }}" @selected($selected('visibility', $todo->visibility?->value ?? 'personal') === $visibility->value)>
                    {{ $visibility->label() }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="col-md-6">
        <x-form.select name="assignee_id" label="Assignee" :value="$selected('assignee_id', $todo->assignee_id)">
            <option value="">Unassigned</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected($selected('assignee_id', $todo->assignee_id) === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="col-md-6">
        <x-form.select name="department_id" label="Department" :value="$selected('department_id', $todo->department_id)">
            <option value="">None</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected($selected('department_id', $todo->department_id) === (string) $department->id)>
                    {{ $department->department_name }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="col-md-4">
        <x-form.input name="start_date" label="Start date" type="date"
                      :value="$selected('start_date', $todo->start_date?->toDateString())" />
    </div>

    <div class="col-md-4">
        <x-form.input name="due_date" label="Due date" type="date"
                      :value="$selected('due_date', $todo->due_date?->toDateString())"
                      help="Optional — an undated To-Do is valid." />
    </div>

    <div class="col-md-4">
        <x-form.input name="due_time" label="Due time" type="time"
                      :value="$selected('due_time', $todo->due_time)" />
    </div>

    <div class="col-md-6">
        <x-form.input name="estimated_minutes" label="Estimated minutes" type="number" min="0"
                      :value="$selected('estimated_minutes', $todo->estimated_minutes)" />
    </div>

    <div class="col-md-6">
        <x-form.input name="actual_minutes" label="Actual minutes" type="number" min="0"
                      :value="$selected('actual_minutes', $todo->actual_minutes)" />
    </div>
</div>
