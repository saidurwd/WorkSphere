@extends('layouts.app')

@section('title', $todo->title)

@section('content')
    <x-page-header :title="$todo->title" icon="check2-square">
        <x-badge :variant="\App\Support\StatusBadge::variant($todo->status)">
            {{ \App\Support\StatusBadge::label($todo->status) }}
        </x-badge>
        <x-badge :variant="\App\Support\StatusBadge::priorityVariant($todo->priority)">
            {{ \App\Support\StatusBadge::label($todo->priority) }}
        </x-badge>
        @if ($todo->due_date?->isPast() && \App\Enums\WorkItemStatus::from($todo->status->value)->isOpen())
            <x-badge variant="danger" icon="exclamation-triangle">Overdue</x-badge>
        @endif
        @can('update', $todo)
            <x-btn :href="route('todos.edit', $todo)" variant="outline-secondary" icon="pencil">Edit</x-btn>
        @endcan
        <x-btn :href="route('todos.index')" variant="outline-secondary" icon="arrow-left">Back</x-btn>
    </x-page-header>

    {{-- Lifecycle actions. Real forms, so they work without JavaScript and are
         authorised server-side. --}}
    @canany(['complete', 'update', 'archive', 'delete'], [$todo])
        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap gap-2">
                @can('complete', $todo)
                    @if (\App\Enums\WorkItemStatus::from($todo->status->value)->isOpen())
                        <form action="{{ route('todos.complete', $todo) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check2 me-1"></i>Mark complete
                            </button>
                        </form>
                    @else
                        <form action="{{ route('todos.reopen', $todo) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reopen
                            </button>
                        </form>
                    @endif
                @endcan

                @can('archive', $todo)
                    @if ($todo->status !== \App\Enums\WorkItemStatus::Archived)
                        <form action="{{ route('todos.archive', $todo) }}" method="POST"
                              data-confirm="Archive this To-Do? It can be restored later."
                              data-confirm-button="Archive">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-archive me-1"></i>Archive
                            </button>
                        </form>
                    @else
                        <form action="{{ route('todos.restore', $todo) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-up me-1"></i>Unarchive
                            </button>
                        </form>
                    @endif
                @endcan

                @can('delete', $todo)
                    <form action="{{ route('todos.destroy', $todo) }}" method="POST" class="ms-auto"
                          data-confirm="Delete this To-Do? It can be restored by an administrator."
                          data-confirm-button="Delete" data-confirm-icon="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash3 me-1"></i>Delete
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    @endcanany

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title mb-0">Description</h3></div>
                <div class="card-body">
                    @if ($todo->description)
                        {{-- Escaped. Never {!! !!} on user content. --}}
                        <p class="mb-0" style="white-space: pre-line;">{{ $todo->description }}</p>
                    @else
                        <p class="text-body-secondary mb-0">No description.</p>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Checklist</h3>
                    <span class="text-body-secondary small">{{ $checklist['completed'] }} of {{ $checklist['total'] }}</span>
                </div>

                @if ($checklist['total'] > 0)
                    <div class="card-body border-bottom">
                        {{-- Progress is computed from the items on every render; there
                             is no stored percentage that can go stale. --}}
                        <div class="progress" role="progressbar" aria-label="Checklist progress"
                             aria-valuenow="{{ $checklist['percent'] }}" aria-valuemin="0" aria-valuemax="100"
                             style="height: 0.5rem;">
                            <div class="progress-bar" style="width: {{ $checklist['percent'] }}%"></div>
                        </div>
                    </div>
                @endif

                <ul class="list-group list-group-flush">
                    @forelse ($todo->checklistItems as $item)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <form action="{{ route('todos.checklist.update', [$todo, $item]) }}" method="POST"
                                  class="d-flex align-items-center gap-2 flex-grow-1">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="title" value="{{ $item->title }}">
                                <input type="hidden" name="is_completed" value="{{ $item->is_completed ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none"
                                        aria-label="{{ $item->is_completed ? 'Mark incomplete' : 'Mark complete' }}: {{ $item->title }}">
                                    <i class="bi {{ $item->is_completed ? 'bi-check-circle-fill text-success' : 'bi-circle' }}"></i>
                                </button>
                                <span @class(['text-decoration-line-through text-body-secondary' => $item->is_completed])>
                                    {{ $item->title }}
                                </span>
                            </form>

                            @can('update', $todo)
                                <form action="{{ route('todos.checklist.destroy', [$todo, $item]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                            aria-label="Remove {{ $item->title }}">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">No checklist items yet.</li>
                    @endforelse
                </ul>

                @can('update', $todo)
                    <div class="card-footer">
                        <form action="{{ route('todos.checklist.store', $todo) }}" method="POST" class="d-flex gap-2">
                            @csrf
                            <label for="checklist-title" class="visually-hidden">Checklist item title</label>
                            <input id="checklist-title" type="text" name="title" class="form-control"
                                   placeholder="Add a step…" maxlength="255" required>
                            <button type="submit" class="btn btn-outline-primary">Add</button>
                        </form>
                    </div>
                @endcan
            </div>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title mb-0">Comments ({{ $todo->comments_count }})</h3></div>

                <ul class="list-group list-group-flush">
                    @forelse ($todo->comments->sortByDesc('created_at') as $comment)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <strong>{{ $comment->author?->name ?? 'Removed user' }}</strong>
                                <small class="text-body-secondary">{{ $comment->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-0" style="white-space: pre-line;">{{ $comment->body }}</p>
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">No comments yet.</li>
                    @endforelse
                </ul>

                @can('comment', $todo)
                    <div class="card-footer">
                        <form action="{{ route('todos.comments.store', $todo) }}" method="POST">
                            @csrf
                            <label for="comment-body" class="form-label">
                                Add a comment
                                <span class="text-body-secondary fw-normal">— use <code>@name</code> to mention someone.</span>
                            </label>
                            <textarea id="comment-body" name="body" rows="3" maxlength="5000" required
                                      class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-sm btn-primary mt-2">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Post comment
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Plain cards rather than <x-detail-card>: that component renders its
                 `items` prop or an empty message and ignores the slot entirely, so a
                 custom list placed inside it would silently vanish. --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-body-secondary"></i>Details
                    </h3>
                </div>
                <div class="card-body">
                    <x-detail-list :items="[
                        ['label' => 'Status', 'value' => \App\Support\StatusBadge::label($todo->status)],
                        ['label' => 'Priority', 'value' => \App\Support\StatusBadge::label($todo->priority)],
                        ['label' => 'Visibility', 'value' => $todo->visibility?->label() ?? '—'],
                        ['label' => 'Assignee', 'value' => $todo->assignee?->name ?? 'Unassigned'],
                        ['label' => 'Creator', 'value' => $todo->creator?->name ?? '—'],
                        ['label' => 'Department', 'value' => $todo->department?->department_name ?? '—'],
                        ['label' => 'Start', 'value' => $todo->start_date?->format('M d, Y') ?? '—'],
                        ['label' => 'Due', 'value' => $todo->due_date?->format('M d, Y') ?? '—'],
                        ['label' => 'Completed', 'value' => $todo->completed_at?->format('M d, Y H:i') ?? '—'],
                        ['label' => 'Repeats', 'value' => $todo->isRecurring() ? 'Yes' : 'No'],
                    ]" />
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-people text-body-secondary"></i>Watchers
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse ($todo->watchers as $watcher)
                            <li class="list-group-item d-flex align-items-center justify-content-between px-0">
                                <span>{{ $watcher->user?->name ?? 'Removed user' }}</span>
                                @can('update', $todo)
                                    <form action="{{ route('todos.watchers.destroy', [$todo, $watcher->user_id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                                aria-label="Remove {{ $watcher->user?->name }} as watcher">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                @endcan
                            </li>
                        @empty
                            <li class="list-group-item text-body-secondary px-0">No watchers.</li>
                        @endforelse
                    </ul>

                    @can('update', $todo)
                        <form action="{{ route('todos.watchers.store', $todo) }}" method="POST" class="mt-2">
                            @csrf
                            <label for="watcher-user" class="visually-hidden">Add a watcher</label>
                            <select id="watcher-user" name="user_id" class="form-select form-select-sm" required>
                                <option value="">Add a watcher…</option>
                                @foreach ($watchers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-link-45deg text-body-secondary"></i>Links
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse ($linkedTargets as $entry)
                            <li class="list-group-item px-0">
                                <x-badge variant="info">{{ $entry['link']->link_type->label() }}</x-badge>
                                {{ class_basename($entry['target']::class) }} #{{ $entry['target']->getKey() }}
                            </li>
                        @empty
                            <li class="list-group-item text-body-secondary px-0">No outgoing links.</li>
                        @endforelse
                    </ul>

                    @if ($reverseLinks->isNotEmpty())
                        <p class="text-body-secondary small mt-2 mb-1">Linked from</p>
                        <ul class="list-group list-group-flush">
                            @foreach ($reverseLinks as $other)
                                <li class="list-group-item px-0">
                                    <a href="{{ route('todos.show', $other) }}" class="text-decoration-none">
                                        {{ $other->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @can('update', $todo)
                        <form action="{{ route('todos.links.store', $todo) }}" method="POST" class="mt-2 d-flex gap-2">
                            @csrf
                            <label for="link-type" class="visually-hidden">Record type</label>
                            <select id="link-type" name="linkable_type" class="form-select form-select-sm" required>
                                @foreach ($linkableTypes as $type)
                                    <option value="{{ $type }}">{{ \Illuminate\Support\Str::headline($type) }}</option>
                                @endforeach
                            </select>
                            <label for="link-id" class="visually-hidden">Record id</label>
                            <input id="link-id" type="number" name="linkable_id" class="form-control form-control-sm"
                                   min="1" placeholder="#" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Link</button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-paperclip text-body-secondary"></i>Attachments ({{ $todo->attachments_count }})
                    </h3>
                </div>
                <div class="card-body">
                    {{-- No download URL is rendered. Attachments live on a private
                         disk; downloads go through an authorised controller (Phase 11),
                         so a guessed path cannot read one. --}}
                    <ul class="list-group list-group-flush">
                        @forelse ($todo->attachments as $attachment)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                                <span class="text-truncate">{{ $attachment->original_name }}</span>
                                <small class="text-body-secondary">{{ number_format(($attachment->size ?? 0) / 1024, 1) }} KB</small>
                            </li>
                        @empty
                            <li class="list-group-item text-body-secondary px-0">No attachments.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-body-secondary"></i>Activity
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse ($activity as $entry)
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="text-capitalize">{{ str_replace('_', ' ', $entry->action) }}</strong>
                                    <small class="text-body-secondary">{{ $entry->created_at->diffForHumans() }}</small>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-body-secondary px-0">Nothing recorded yet.</li>
                        @endforelse
                    </ul>

                    @if ($activity->hasPages())
                        <div class="mt-2">{{ $activity->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
