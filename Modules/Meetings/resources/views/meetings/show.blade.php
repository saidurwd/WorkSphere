@extends('layouts.app')

@section('title', 'Meeting Details')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => $meeting->title],
    ];
@endphp

@section('content')
<x-page-header :title="$meeting->title" :subtitle="$meeting->meeting_no.' &middot; '.$meeting->meeting_date->format('M d, Y').' &middot; '.$meeting->start_time->format('H:i').' - '.$meeting->end_time->format('H:i')">
    @if($meeting->status === 'scheduled')
        <form action="{{ route('meetings.start', $meeting) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">Start Meeting</button>
        </form>
    @endif
    @if($meeting->status === 'in_progress')
        <form action="{{ route('meetings.complete', $meeting) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">Complete Meeting</button>
        </form>
    @endif
    @if(!in_array($meeting->status, ['completed', 'cancelled']))
        <form action="{{ route('meetings.cancel', $meeting) }}" method="POST" class="d-inline" data-confirm="Cancel this meeting?">
            @csrf
            <button type="submit" class="btn btn-danger">Cancel</button>
        </form>
    @endif
    <x-btn :href="route('meetings.edit', $meeting)" variant="secondary">Edit</x-btn>
    <x-btn :href="route('meetings.print', $meeting)" variant="secondary" icon="printer" target="_blank">Print</x-btn>
    </x-page-header>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Status</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ ucwords(str_replace('_', ' ', $meeting->status)) }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Type</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $meeting->type->name ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Organizer</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $meeting->organizer->name ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Chairperson</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $meeting->chairperson->name ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Department</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ $meeting->department->department_name ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.25rem; font-size: 0.85rem;">Minutes Status</div>
                <div style="font-size: 1rem; font-weight: 600;">{{ ucwords(str_replace('_', ' ', $meeting->minutes_status)) }}</div>
            </div>
        </div>

        @if($meeting->description)
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
            <div style="font-weight: 500; color: var(--muted-foreground); margin-bottom: 0.5rem; font-size: 0.85rem;">Description</div>
            <div style="white-space: pre-wrap; line-height: 1.7;">{{ $meeting->description }}</div>
        </div>
        @endif
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Agenda</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addAgendaModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->agendas->isNotEmpty())
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Presenter</th>
                            <th scope="col">Est. Minutes</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->agendas as $agenda)
                        <tr>
                            <td>{{ $agenda->agenda_no }}</td>
                            <td>
                                <div style="font-weight: 600;">{{ $agenda->title }}</div>
                                @if($agenda->description)
                                <div style="color: var(--muted-foreground); font-size: 0.85rem; margin-top: 0.25rem;">{{ $agenda->description }}</div>
                                @endif
                            </td>
                            <td>{{ $agenda->presentedBy->name ?? 'N/A' }}</td>
                            <td>{{ $agenda->estimated_minutes ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $agenda->status === 'completed' ? 'text-bg-success' : ($agenda->status === 'in_progress' ? 'text-bg-primary' : 'bg-secondary text-dark') }}">
                                    {{ ucwords(str_replace('_', ' ', $agenda->status)) }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditAgendaModal({{ $agenda->id }}, '{{ addslashes($agenda->title) }}', '{{ addslashes($agenda->description ?? '') }}', '{{ $agenda->presented_by ?? '' }}', '{{ $agenda->estimated_minutes ?? '' }}', '{{ $agenda->status }}', '{{ $agenda->sort_order }}', '{{ $agenda->agenda_no }}')">Edit</button>
                                    <form action="{{ route('meetings.agendas.destroy', [$meeting, $agenda]) }}" method="POST" style="display: inline;" data-confirm="Delete this agenda item?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="padding: 2rem 0;">
                <p style="margin: 0; color: var(--muted-foreground);">No agenda items yet.</p>
            </div>
        @endif
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Decisions</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addDecisionModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->decisions->isNotEmpty())
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                            <th scope="col">Approved By</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->decisions as $decision)
                        <tr>
                            <td>{{ $decision->decision_no }}</td>
                            <td>
                                <div style="font-weight: 600;">{{ $decision->decision_title }}</div>
                                @if($decision->decision_description)
                                <div style="color: var(--muted-foreground); font-size: 0.85rem; margin-top: 0.25rem;">{{ $decision->decision_description }}</div>
                                @endif
                                @if($decision->remarks)
                                <div style="color: var(--muted-foreground); font-size: 0.85rem; margin-top: 0.25rem; font-style: italic;">{{ $decision->remarks }}</div>
                                @endif
                            </td>
                            <td>
                                <x-badge variant="secondary">{{ ucwords(str_replace('_', ' ', $decision->decision_type)) }}</x-badge>
                            </td>
                            <td>
                                <span class="badge {{ $decision->decision_status === 'active' ? 'text-bg-success' : ($decision->decision_status === 'cancelled' ? 'text-bg-danger' : 'bg-secondary text-dark') }}">
                                    {{ ucwords($decision->decision_status) }}
                                </span>
                            </td>
                            <td>{{ $decision->approvedBy->name ?? 'N/A' }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditDecisionModal({{ $decision->id }}, '{{ addslashes($decision->decision_title) }}', '{{ addslashes($decision->decision_description ?? '') }}', '{{ $decision->decision_type }}', '{{ $decision->decision_status }}', '{{ $decision->decision_date ?? '' }}', '{{ $decision->approved_by ?? '' }}', '{{ $decision->effective_date ?? '' }}', '{{ addslashes($decision->remarks ?? '') }}', '{{ $decision->decision_no }}')">Edit</button>
                                    <form action="{{ route('meetings.decisions.destroy', [$meeting, $decision]) }}" method="POST" style="display: inline;" data-confirm="Delete this decision?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="padding: 2rem 0;">
                <p style="margin: 0; color: var(--muted-foreground);">No decisions recorded yet.</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Decision Modal -->
<div class="modal fade" id="addDecisionModal" tabindex="-1" aria-labelledby="addDecisionModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="addDecisionModal-label">Add Decision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form action="{{ route('meetings.decisions.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label for="addDecisionModal-decision_no" class="form-label">Decision # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="decision_no" id="addDecisionModal-decision_no" class="form-control" value="{{ old('decision_no', $meeting->decisions->count() + 1) }}" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-decision_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="decision_title" id="addDecisionModal-decision_title" class="form-control" value="{{ old('decision_title') }}" required>
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-decision_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="decision_type" id="addDecisionModal-decision_type" class="form-select">
                        <option value="approved" {{ old('decision_type') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ old('decision_type') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="deferred" {{ old('decision_type') === 'deferred' ? 'selected' : '' }}>Deferred</option>
                        <option value="noted" {{ old('decision_type') === 'noted' ? 'selected' : '' }}>Noted</option>
                        <option value="further_discussion_required" {{ old('decision_type') === 'further_discussion_required' ? 'selected' : '' }}>Further Discussion Required</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-decision_status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="decision_status" id="addDecisionModal-decision_status" class="form-select">
                        <option value="active" {{ old('decision_status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="superseded" {{ old('decision_status') === 'superseded' ? 'selected' : '' }}>Superseded</option>
                        <option value="cancelled" {{ old('decision_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-approved_by" class="form-label">Approved By</label>
                    <select name="approved_by" id="addDecisionModal-approved_by" class="form-select">
                        <option value="">Select Approver</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('approved_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-decision_date" class="form-label">Decision Date</label>
                    <input type="date" name="decision_date" id="addDecisionModal-decision_date" class="form-control" value="{{ old('decision_date') }}">
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-effective_date" class="form-label">Effective Date</label>
                    <input type="date" name="effective_date" id="addDecisionModal-effective_date" class="form-control" value="{{ old('effective_date') }}">
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-decision_description" class="form-label">Description</label>
                    <textarea name="decision_description" id="addDecisionModal-decision_description" class="form-control" rows="3">{{ old('decision_description') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="addDecisionModal-remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="addDecisionModal-remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Decision</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<!-- Edit Decision Modal -->
<div class="modal fade" id="editDecisionModal" tabindex="-1" aria-labelledby="editDecisionModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="editDecisionModal-label">Edit Decision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form id="editDecisionModal-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editDecisionModal-decision_no" class="form-label">Decision # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="decision_no" id="editDecisionModal-decision_no" class="form-control" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-decision_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="decision_title" id="editDecisionModal-decision_title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-decision_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="decision_type" id="editDecisionModal-decision_type" class="form-select">
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="deferred">Deferred</option>
                        <option value="noted">Noted</option>
                        <option value="further_discussion_required">Further Discussion Required</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-decision_status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="decision_status" id="editDecisionModal-decision_status" class="form-select">
                        <option value="active">Active</option>
                        <option value="superseded">Superseded</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-approved_by" class="form-label">Approved By</label>
                    <select name="approved_by" id="editDecisionModal-approved_by" class="form-select">
                        <option value="">Select Approver</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-decision_date" class="form-label">Decision Date</label>
                    <input type="date" name="decision_date" id="editDecisionModal-decision_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-effective_date" class="form-label">Effective Date</label>
                    <input type="date" name="effective_date" id="editDecisionModal-effective_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-decision_description" class="form-label">Description</label>
                    <textarea name="decision_description" id="editDecisionModal-decision_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="editDecisionModal-remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="editDecisionModal-remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Decision</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Action Items</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addActionItemModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->actionItems->isNotEmpty())
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Assigned To</th>
                            <th scope="col">Department</th>
                            <th scope="col">Due Date</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                            <th scope="col">Task</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->actionItems as $item)
                        <tr>
                            <td>{{ $item->action_no }}</td>
                            <td>
                                <div style="font-weight: 600;">{{ $item->title }}</div>
                                @if($item->description)
                                <div style="color: var(--muted-foreground); font-size: 0.85rem; margin-top: 0.25rem;">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td>{{ $item->assignedTo->name ?? 'N/A' }}</td>
                            <td>{{ $item->assignedDepartment->department_name ?? 'N/A' }}</td>
                            <td>{{ $item->due_date ? $item->due_date->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <x-badge variant="secondary">{{ ucwords($item->priority) }}</x-badge>
                            </td>
                            <td>
                                <span class="badge {{ $item->status === 'completed' ? 'text-bg-success' : ($item->status === 'in_progress' ? 'text-bg-primary' : 'bg-secondary text-dark') }}">
                                    {{ ucwords(str_replace('_', ' ', $item->status)) }}
                                </span>
                                @if($item->isOverdue())
                                <span class="badge text-bg-danger" style="margin-left: 0.25rem;">Overdue</span>
                                @endif
                            </td>
                            <td>
                                @if($item->task)
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <a href="{{ route('tasks.show', $item->task) }}" style="font-weight: 500; text-decoration: none;">
                                            {{ $item->task->task_no ?? ('Task #'.$item->task->id) }}
                                        </a>
                                        {{-- `Task::$status` is cast to `WorkItemStatus`, so the old
                                             `$item->task->status === 'completed'` was an enum against
                                             a string and never true: every linked task badged grey.
                                             `str_replace` on the enum was a hard TypeError. --}}
                                        <span class="badge {{ \App\Support\StatusBadge::statusBadgeClass($item->task->status) }}">
                                            {{ \App\Support\StatusBadge::label($item->task->status) }}
                                        </span>
                                    </div>
                                @else
                                    <span style="color: var(--muted-foreground); font-size: 0.85rem;">No task</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditActionItemModal({{ $item->id }}, '{{ addslashes($item->title) }}', '{{ addslashes($item->description ?? '') }}', '{{ $item->assigned_to ?? '' }}', '{{ $item->assigned_department_id ?? '' }}', '{{ $item->priority }}', '{{ $item->due_date ?? '' }}', '{{ $item->status }}', '{{ $item->action_no }}')">Edit</button>
                                    @if(!$item->task)
                                        <button type="button" class="btn btn-sm btn-primary" onclick="openCreateTaskModal({{ $item->id }}, '{{ addslashes($item->title) }}', '{{ addslashes($item->description ?? '') }}', '{{ $item->due_date ?? '' }}', '{{ $item->assigned_to ?? '' }}')">Create Task</button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="openLinkTaskModal({{ $item->id }})">Link Task</button>
                                    @else
                                        <form action="{{ route('meetings.action-items.tasks.unlink', [$meeting, $item]) }}" method="POST" style="display: inline;" data-confirm="Unlink this task from the action item?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-warning">Unlink</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('meetings.action-items.destroy', [$meeting, $item]) }}" method="POST" style="display: inline;" data-confirm="Delete this action item?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="padding: 2rem 0;">
                <p style="margin: 0; color: var(--muted-foreground);">No action items yet.</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Action Item Modal -->
<div class="modal fade" id="addActionItemModal" tabindex="-1" aria-labelledby="addActionItemModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="addActionItemModal-label">Add Action Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form action="{{ route('meetings.action-items.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label for="addActionItemModal-action_no" class="form-label">Action # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="action_no" id="addActionItemModal-action_no" class="form-control" value="{{ old('action_no', $meeting->actionItems->count() + 1) }}" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="addActionItemModal-title" class="form-control" value="{{ old('title') }}" required>
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-description" class="form-label">Description</label>
                    <textarea name="description" id="addActionItemModal-description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-assigned_to" class="form-label">Assigned To</label>
                    <select name="assigned_to" id="addActionItemModal-assigned_to" class="form-select">
                        <option value="">Select Assignee</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-assigned_department_id" class="form-label">Department</label>
                    <select name="assigned_department_id" id="addActionItemModal-assigned_department_id" class="form-select">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('assigned_department_id') == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-priority" class="form-label">Priority <span style="color: var(--danger);">*</span></label>
                    <select name="priority" id="addActionItemModal-priority" class="form-select">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-due_date" class="form-label">Due Date</label>
                    <input type="date" name="due_date" id="addActionItemModal-due_date" class="form-control" value="{{ old('due_date') }}">
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="status" id="addActionItemModal-status" class="form-select">
                        <option value="open" {{ old('status') === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addActionItemModal-remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="addActionItemModal-remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Action Item</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<!-- Edit Action Item Modal -->
<div class="modal fade" id="editActionItemModal" tabindex="-1" aria-labelledby="editActionItemModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="editActionItemModal-label">Edit Action Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form id="editActionItemModal-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editActionItemModal-action_no" class="form-label">Action # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="action_no" id="editActionItemModal-action_no" class="form-control" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="editActionItemModal-title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-description" class="form-label">Description</label>
                    <textarea name="description" id="editActionItemModal-description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-assigned_to" class="form-label">Assigned To</label>
                    <select name="assigned_to" id="editActionItemModal-assigned_to" class="form-select">
                        <option value="">Select Assignee</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-assigned_department_id" class="form-label">Department</label>
                    <select name="assigned_department_id" id="editActionItemModal-assigned_department_id" class="form-select">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-priority" class="form-label">Priority <span style="color: var(--danger);">*</span></label>
                    <select name="priority" id="editActionItemModal-priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-due_date" class="form-label">Due Date</label>
                    <input type="date" name="due_date" id="editActionItemModal-due_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="status" id="editActionItemModal-status" class="form-select">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editActionItemModal-remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="editActionItemModal-remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Action Item</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="createTaskModal-label">Create Task from Action Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form id="createTaskModal-form" method="POST">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label for="createTaskModal-task_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="createTaskModal-task_title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="createTaskModal-task_description" class="form-label">Description</label>
                    <textarea name="description" id="createTaskModal-task_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="createTaskModal-task_priority" class="form-label">Priority <span style="color: var(--danger);">*</span></label>
                    <select name="priority" id="createTaskModal-task_priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="createTaskModal-task_status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="status" id="createTaskModal-task_status" class="form-select">
                        <option value="pending" selected>Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="createTaskModal-task_due_date" class="form-label">Due Date</label>
                    <input type="date" name="due_date" id="createTaskModal-task_due_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="createTaskModal-task_responsible_user_id" class="form-label">Responsible User</label>
                    <select name="responsible_user_id" id="createTaskModal-task_responsible_user_id" class="form-select">
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Task</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<!-- Link Task Modal -->
<div class="modal fade" id="linkTaskModal" tabindex="-1" aria-labelledby="linkTaskModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="linkTaskModal-label">Link Existing Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form id="linkTaskModal-form" method="POST">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label for="linkTaskModal-link_task_id" class="form-label">Select Task <span style="color: var(--danger);">*</span></label>
                    <select name="task_id" id="linkTaskModal-link_task_id" class="form-select" required>
                        <option value="">Select a task</option>
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}">
                                {{ $task->task_no ?? 'Task #'.$task->id }} - {{ $task->title }} ({{ \App\Support\StatusBadge::label($task->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Link Task</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Participants</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addParticipantModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->participants->isNotEmpty())
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Attendance</th>
                            <th scope="col">Remarks</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->participants as $participant)
                        <tr>
                            <td>{{ $participant->user->name ?? 'N/A' }}</td>
                            <td>
                                <x-badge variant="secondary">{{ ucwords($participant->participant_type) }}</x-badge>
                            </td>
                            <td>
                                <span class="badge {{ $participant->attendance_status === 'present' ? 'text-bg-success' : ($participant->attendance_status === 'accepted' ? 'text-bg-primary' : 'bg-secondary text-dark') }}">
                                    {{ ucwords($participant->attendance_status) }}
                                </span>
                            </td>
                            <td>{{ $participant->remarks ?? 'N/A' }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditParticipantModal({{ $participant->id }}, '{{ $participant->user_id ?? '' }}', '{{ $participant->participant_type }}', '{{ $participant->attendance_status }}', '{{ addslashes($participant->remarks ?? '') }}')">Edit</button>
                                    <form action="{{ route('meetings.participants.destroy', [$meeting, $participant]) }}" method="POST" style="display: inline;" data-confirm="Remove this participant?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="padding: 2rem 0;">
                <p style="margin: 0; color: var(--muted-foreground);">No participants yet.</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Participant Modal -->
<div class="modal fade" id="addParticipantModal" tabindex="-1" aria-labelledby="addParticipantModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="addParticipantModal-label">Add Participant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form action="{{ route('meetings.participants.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label for="addParticipantModal-user_id" class="form-label">User <span style="color: var(--danger);">*</span></label>
                    <select name="user_id" id="addParticipantModal-user_id" class="form-select" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addParticipantModal-participant_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="participant_type" id="addParticipantModal-participant_type" class="form-select">
                        <option value="organizer" {{ old('participant_type') === 'organizer' ? 'selected' : '' }}>Organizer</option>
                        <option value="chairperson" {{ old('participant_type') === 'chairperson' ? 'selected' : '' }}>Chairperson</option>
                        <option value="member" {{ old('participant_type') === 'member' ? 'selected' : '' }}>Member</option>
                        <option value="guest" {{ old('participant_type') === 'guest' ? 'selected' : '' }}>Guest</option>
                        <option value="presenter" {{ old('participant_type') === 'presenter' ? 'selected' : '' }}>Presenter</option>
                        <option value="observer" {{ old('participant_type') === 'observer' ? 'selected' : '' }}>Observer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addParticipantModal-attendance_status" class="form-label">Attendance <span style="color: var(--danger);">*</span></label>
                    <select name="attendance_status" id="addParticipantModal-attendance_status" class="form-select">
                        <option value="invited" {{ old('attendance_status') === 'invited' ? 'selected' : '' }}>Invited</option>
                        <option value="accepted" {{ old('attendance_status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="declined" {{ old('attendance_status') === 'declined' ? 'selected' : '' }}>Declined</option>
                        <option value="present" {{ old('attendance_status') === 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ old('attendance_status') === 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="apology" {{ old('attendance_status') === 'apology' ? 'selected' : '' }}>Apology</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addParticipantModal-remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="addParticipantModal-remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Participant</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<!-- Edit Participant Modal -->
<div class="modal fade" id="editParticipantModal" tabindex="-1" aria-labelledby="editParticipantModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="editParticipantModal-label">Edit Participant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form id="editParticipantModal-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editParticipantModal-user_id" class="form-label">User <span style="color: var(--danger);">*</span></label>
                    <select name="user_id" id="editParticipantModal-user_id" class="form-select" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editParticipantModal-participant_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="participant_type" id="editParticipantModal-participant_type" class="form-select">
                        <option value="organizer">Organizer</option>
                        <option value="chairperson">Chairperson</option>
                        <option value="member">Member</option>
                        <option value="guest">Guest</option>
                        <option value="presenter">Presenter</option>
                        <option value="observer">Observer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editParticipantModal-attendance_status" class="form-label">Attendance <span style="color: var(--danger);">*</span></label>
                    <select name="attendance_status" id="editParticipantModal-attendance_status" class="form-select">
                        <option value="invited">Invited</option>
                        <option value="accepted">Accepted</option>
                        <option value="declined">Declined</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="apology">Apology</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editParticipantModal-remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="editParticipantModal-remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Participant</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Attachments</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addAttachmentModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->attachments->isNotEmpty())
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">File Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Size</th>
                            <th scope="col">Description</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->attachments as $attachment)
                        <tr>
                            <td>
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank">{{ $attachment->file_name }}</a>
                            </td>
                            <td>{{ $attachment->file_type ?? 'N/A' }}</td>
                            <td>{{ $attachment->file_size ? round($attachment->file_size / 1024, 1) . ' KB' : 'N/A' }}</td>
                            <td>{{ $attachment->description ?: 'N/A' }}</td>
                            <td>
                                <form action="{{ route('meetings.attachments.destroy', [$meeting, $attachment]) }}" method="POST" style="display: inline;" data-confirm="Delete this attachment?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="padding: 2rem 0;">
                <p style="margin: 0; color: var(--muted-foreground);">No attachments yet.</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Attachment Modal -->
<div class="modal fade" id="addAttachmentModal" tabindex="-1" aria-labelledby="addAttachmentModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="addAttachmentModal-label">Add Attachment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form action="{{ route('meetings.attachments.store', $meeting) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label for="addAttachmentModal-file" class="form-label">File <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="file" id="addAttachmentModal-file" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="addAttachmentModal-description" class="form-label">Description</label>
                    <textarea name="description" id="addAttachmentModal-description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload Attachment</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Minutes Actions</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @if(in_array($meeting->minutes_status, ['draft', 'prepared']))
                <form action="{{ route('meetings.minutes.submit', $meeting) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">Submit Minutes</button>
                </form>
            @endif
            @if(in_array($meeting->minutes_status, ['submitted', 'under_review']))
                <form action="{{ route('meetings.minutes.approve', $meeting) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve Minutes</button>
                </form>
                <button type="button" class="btn btn-warning" onclick="document.getElementById('return-minutes-form').submit()">Return Minutes</button>
                <form action="{{ route('meetings.minutes.return', $meeting) }}" method="POST" id="return-minutes-form" style="display: none;">
                    @csrf
                    <textarea name="comments" placeholder="Return comments" required></textarea>
                </form>
            @endif
            @if($meeting->minutes_status === 'approved')
                <form action="{{ route('meetings.minutes.publish', $meeting) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">Publish Minutes</button>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Add Agenda Modal -->
<div class="modal fade" id="addAgendaModal" tabindex="-1" aria-labelledby="addAgendaModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="addAgendaModal-label">Add Agenda Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form action="{{ route('meetings.agendas.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label for="addAgendaModal-agenda_no" class="form-label">Agenda # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="agenda_no" id="addAgendaModal-agenda_no" class="form-control" value="{{ old('agenda_no', $meeting->agendas->count() + 1) }}" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="addAgendaModal-title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="addAgendaModal-title" class="form-control" value="{{ old('title') }}" required>
                </div>
                <div class="mb-3">
                    <label for="addAgendaModal-presented_by" class="form-label">Presented By</label>
                    <select name="presented_by" id="addAgendaModal-presented_by" class="form-select">
                        <option value="">Select Presenter</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('presented_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addAgendaModal-estimated_minutes" class="form-label">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" id="addAgendaModal-estimated_minutes" class="form-control" value="{{ old('estimated_minutes') }}" min="1">
                </div>
                <div class="mb-3">
                    <label for="addAgendaModal-status" class="form-label">Status</label>
                    <select name="status" id="addAgendaModal-status" class="form-select">
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="skipped" {{ old('status') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="addAgendaModal-sort_order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="addAgendaModal-sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                </div>
                <div class="mb-3">
                    <label for="addAgendaModal-description" class="form-label">Description</label>
                    <textarea name="description" id="addAgendaModal-description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Agenda</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<!-- Edit Agenda Modal -->
<div class="modal fade" id="editAgendaModal" tabindex="-1" aria-labelledby="editAgendaModal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="editAgendaModal-label">Edit Agenda Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        <form id="editAgendaModal-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="mb-3">
                    <label for="editAgendaModal-agenda_no" class="form-label">Agenda # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="agenda_no" id="editAgendaModal-agenda_no" class="form-control" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="editAgendaModal-title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="editAgendaModal-title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="editAgendaModal-presented_by" class="form-label">Presented By</label>
                    <select name="presented_by" id="editAgendaModal-presented_by" class="form-select">
                        <option value="">Select Presenter</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editAgendaModal-estimated_minutes" class="form-label">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" id="editAgendaModal-estimated_minutes" class="form-control" min="1">
                </div>
                <div class="mb-3">
                    <label for="editAgendaModal-status" class="form-label">Status</label>
                    <select name="status" id="editAgendaModal-status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="skipped">Skipped</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="editAgendaModal-sort_order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="editAgendaModal-sort_order" class="form-control" min="0">
                </div>
                <div class="mb-3">
                    <label for="editAgendaModal-description" class="form-label">Description</label>
                    <textarea name="description" id="editAgendaModal-description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Agenda</button>
            </div>
        </form>
        </div>
    </div>
</div></div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Version History</h2>
    </div>
    <div class="card-body">
        @if($meeting->versions->isNotEmpty())
            @foreach($meeting->versions as $version)
            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                <div style="font-weight: 600;">Version {{ $version->version_no }}</div>
                <div style="color: var(--muted-foreground); font-size: 0.85rem;">{{ $version->change_summary }}</div>
                <div style="font-size: 0.85rem; color: var(--muted-foreground);">By {{ $version->createdBy->name ?? 'N/A' }} on {{ $version->created_at->format('M d, Y H:i') }}</div>
            </div>
            @endforeach
        @else
            <div class="empty-state" style="padding: 2rem 0;">
                <p style="margin: 0; color: var(--muted-foreground);">No version history yet.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Bootstrap 5 modal helpers. The dialogs themselves are plain Bootstrap
    // markup now, so these simply delegate to Bootstrap's Modal API.
    function openModal(id) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
    }

    function closeModal(id) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
    }

    function openEditAgendaModal(id, title, description, presentedBy, estimatedMinutes, status, sortOrder, agendaNo) {
        document.getElementById('editAgendaModal-form').action = '/meetings/{{ $meeting->id }}/agendas/' + id;
        document.getElementById('editAgendaModal-agenda_no').value = agendaNo;
        document.getElementById('editAgendaModal-title').value = title;
        document.getElementById('editAgendaModal-description').value = description;
        document.getElementById('editAgendaModal-presented_by').value = presentedBy;
        document.getElementById('editAgendaModal-estimated_minutes').value = estimatedMinutes;
        document.getElementById('editAgendaModal-status').value = status;
        document.getElementById('editAgendaModal-sort_order').value = sortOrder;
        openModal('editAgendaModal');
    }

    function openEditDecisionModal(id, title, description, type, status, decisionDate, approvedBy, effectiveDate, remarks, decisionNo) {
        document.getElementById('editDecisionModal-form').action = '/meetings/{{ $meeting->id }}/decisions/' + id;
        document.getElementById('editDecisionModal-decision_no').value = decisionNo;
        document.getElementById('editDecisionModal-decision_title').value = title;
        document.getElementById('editDecisionModal-decision_description').value = description;
        document.getElementById('editDecisionModal-decision_type').value = type;
        document.getElementById('editDecisionModal-decision_status').value = status;
        document.getElementById('editDecisionModal-decision_date').value = decisionDate;
        document.getElementById('editDecisionModal-approved_by').value = approvedBy;
        document.getElementById('editDecisionModal-effective_date').value = effectiveDate;
        document.getElementById('editDecisionModal-remarks').value = remarks;
        openModal('editDecisionModal');
    }

    function openEditActionItemModal(id, title, description, assignedTo, assignedDepartment, priority, dueDate, status, actionNo) {
        document.getElementById('editActionItemModal-form').action = '/meetings/{{ $meeting->id }}/action-items/' + id;
        document.getElementById('editActionItemModal-action_no').value = actionNo;
        document.getElementById('editActionItemModal-title').value = title;
        document.getElementById('editActionItemModal-description').value = description;
        document.getElementById('editActionItemModal-assigned_to').value = assignedTo;
        document.getElementById('editActionItemModal-assigned_department_id').value = assignedDepartment;
        document.getElementById('editActionItemModal-priority').value = priority;
        document.getElementById('editActionItemModal-due_date').value = dueDate;
        document.getElementById('editActionItemModal-status').value = status;
        openModal('editActionItemModal');
    }

    function openEditParticipantModal(id, userId, participantType, attendanceStatus, remarks) {
        document.getElementById('editParticipantModal-form').action = '/meetings/{{ $meeting->id }}/participants/' + id;
        document.getElementById('editParticipantModal-user_id').value = userId;
        document.getElementById('editParticipantModal-participant_type').value = participantType;
        document.getElementById('editParticipantModal-attendance_status').value = attendanceStatus;
        document.getElementById('editParticipantModal-remarks').value = remarks;
        openModal('editParticipantModal');
    }

    function openCreateTaskModal(actionItemId, title, description, dueDate, assignedTo) {
        document.getElementById('createTaskModal-form').action = '/meetings/{{ $meeting->id }}/action-items/' + actionItemId + '/tasks';
        document.getElementById('createTaskModal-task_title').value = title || '';
        document.getElementById('createTaskModal-task_description').value = description || '';
        document.getElementById('createTaskModal-task_priority').value = 'medium';
        document.getElementById('createTaskModal-task_status').value = 'pending';
        document.getElementById('createTaskModal-task_due_date').value = dueDate || '';
        document.getElementById('createTaskModal-task_responsible_user_id').value = assignedTo || '';
        openModal('createTaskModal');
    }

    function openLinkTaskModal(actionItemId) {
        document.getElementById('linkTaskModal-form').action = '/meetings/{{ $meeting->id }}/action-items/' + actionItemId + '/tasks/link';
        document.getElementById('linkTaskModal-link_task_id').value = '';
        openModal('linkTaskModal');
    }
</script>
@endpush
@endsection
