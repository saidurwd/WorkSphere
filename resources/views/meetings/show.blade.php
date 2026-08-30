@extends('tyro-dashboard::layouts.admin')

@section('title', 'Meeting Details')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $meeting->title }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $meeting->title }}</h1>
            <p class="page-description">{{ $meeting->meeting_no }} &middot; {{ $meeting->meeting_date->format('M d, Y') }} &middot; {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            @if($meeting->status === 'scheduled')
                <form action="{{ route('meetings.start', $meeting) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">Start Meeting</button>
                </form>
            @endif
            @if($meeting->status === 'in_progress')
                <form action="{{ route('meetings.complete', $meeting) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">Complete Meeting</button>
                </form>
            @endif
            @if(!in_array($meeting->status, ['completed', 'cancelled']))
                <form action="{{ route('meetings.cancel', $meeting) }}" method="POST" style="display: inline;" onsubmit="return confirm('Cancel this meeting?')">
                    @csrf
                    <button type="submit" class="btn btn-danger">Cancel</button>
                </form>
            @endif
            <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('meetings.print', $meeting) }}" target="_blank" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-1.5M21 3v1.5M21 21v-1.5M6.75 6.75h12M6.75 17.25h12M3 12h18" />
                </svg>
                Print
            </a>
        </div>
    </div>
</div>

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
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Presenter</th>
                            <th>Est. Minutes</th>
                            <th>Status</th>
                            <th>Actions</th>
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
                                <span class="badge {{ $agenda->status === 'completed' ? 'badge-success' : ($agenda->status === 'in_progress' ? 'badge-primary' : 'badge-secondary') }}">
                                    {{ ucwords(str_replace('_', ' ', $agenda->status)) }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditAgendaModal({{ $agenda->id }}, '{{ addslashes($agenda->title) }}', '{{ addslashes($agenda->description ?? '') }}', '{{ $agenda->presented_by ?? '' }}', '{{ $agenda->estimated_minutes ?? '' }}', '{{ $agenda->status }}', '{{ $agenda->sort_order }}', '{{ $agenda->agenda_no }}')">Edit</button>
                                    <form action="{{ route('meetings.agendas.destroy', [$meeting, $agenda]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this agenda item?')">
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
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Approved By</th>
                            <th>Actions</th>
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
                                <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $decision->decision_type)) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $decision->decision_status === 'active' ? 'badge-success' : ($decision->decision_status === 'cancelled' ? 'badge-danger' : 'badge-secondary') }}">
                                    {{ ucwords($decision->decision_status) }}
                                </span>
                            </td>
                            <td>{{ $decision->approvedBy->name ?? 'N/A' }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditDecisionModal({{ $decision->id }}, '{{ addslashes($decision->decision_title) }}', '{{ addslashes($decision->decision_description ?? '') }}', '{{ $decision->decision_type }}', '{{ $decision->decision_status }}', '{{ $decision->decision_date ?? '' }}', '{{ $decision->approved_by ?? '' }}', '{{ $decision->effective_date ?? '' }}', '{{ addslashes($decision->remarks ?? '') }}', '{{ $decision->decision_no }}')">Edit</button>
                                    <form action="{{ route('meetings.decisions.destroy', [$meeting, $decision]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this decision?')">
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
<div class="modal-overlay" id="addDecisionModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Decision</h3>
            <button type="button" class="modal-close" onclick="closeModal('addDecisionModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('meetings.decisions.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="decision_no" class="form-label">Decision # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="decision_no" id="decision_no" class="form-input" value="{{ old('decision_no', $meeting->decisions->count() + 1) }}" min="1" required>
                </div>
                <div class="form-group">
                    <label for="decision_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="decision_title" id="decision_title" class="form-input" value="{{ old('decision_title') }}" required>
                </div>
                <div class="form-group">
                    <label for="decision_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="decision_type" id="decision_type" class="form-select">
                        <option value="approved" {{ old('decision_type') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ old('decision_type') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="deferred" {{ old('decision_type') === 'deferred' ? 'selected' : '' }}>Deferred</option>
                        <option value="noted" {{ old('decision_type') === 'noted' ? 'selected' : '' }}>Noted</option>
                        <option value="further_discussion_required" {{ old('decision_type') === 'further_discussion_required' ? 'selected' : '' }}>Further Discussion Required</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="decision_status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="decision_status" id="decision_status" class="form-select">
                        <option value="active" {{ old('decision_status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="superseded" {{ old('decision_status') === 'superseded' ? 'selected' : '' }}>Superseded</option>
                        <option value="cancelled" {{ old('decision_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="approved_by" class="form-label">Approved By</label>
                    <select name="approved_by" id="approved_by" class="form-select">
                        <option value="">Select Approver</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('approved_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="decision_date" class="form-label">Decision Date</label>
                    <input type="date" name="decision_date" id="decision_date" class="form-input" value="{{ old('decision_date') }}">
                </div>
                <div class="form-group">
                    <label for="effective_date" class="form-label">Effective Date</label>
                    <input type="date" name="effective_date" id="effective_date" class="form-input" value="{{ old('effective_date') }}">
                </div>
                <div class="form-group">
                    <label for="decision_description" class="form-label">Description</label>
                    <textarea name="decision_description" id="decision_description" class="form-textarea" rows="3">{{ old('decision_description') }}</textarea>
                </div>
                <div class="form-group">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addDecisionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Decision</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Decision Modal -->
<div class="modal-overlay" id="editDecisionModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Decision</h3>
            <button type="button" class="modal-close" onclick="closeModal('editDecisionModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="editDecisionForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_decision_no" class="form-label">Decision # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="decision_no" id="edit_decision_no" class="form-input" min="1" required>
                </div>
                <div class="form-group">
                    <label for="edit_decision_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="decision_title" id="edit_decision_title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_decision_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="decision_type" id="edit_decision_type" class="form-select">
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="deferred">Deferred</option>
                        <option value="noted">Noted</option>
                        <option value="further_discussion_required">Further Discussion Required</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_decision_status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="decision_status" id="edit_decision_status" class="form-select">
                        <option value="active">Active</option>
                        <option value="superseded">Superseded</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_approved_by" class="form-label">Approved By</label>
                    <select name="approved_by" id="edit_approved_by" class="form-select">
                        <option value="">Select Approver</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_decision_date" class="form-label">Decision Date</label>
                    <input type="date" name="decision_date" id="edit_decision_date" class="form-input">
                </div>
                <div class="form-group">
                    <label for="edit_effective_date" class="form-label">Effective Date</label>
                    <input type="date" name="effective_date" id="edit_effective_date" class="form-input">
                </div>
                <div class="form-group">
                    <label for="edit_decision_description" class="form-label">Description</label>
                    <textarea name="decision_description" id="edit_decision_description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="edit_remarks" class="form-textarea" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editDecisionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Decision</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Action Items</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addActionItemModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->actionItems->isNotEmpty())
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Assigned To</th>
                            <th>Department</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Task</th>
                            <th>Actions</th>
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
                                <span class="badge badge-secondary">{{ ucwords($item->priority) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $item->status === 'completed' ? 'badge-success' : ($item->status === 'in_progress' ? 'badge-primary' : 'badge-secondary') }}">
                                    {{ ucwords(str_replace('_', ' ', $item->status)) }}
                                </span>
                                @if($item->isOverdue())
                                <span class="badge badge-danger" style="margin-left: 0.25rem;">Overdue</span>
                                @endif
                            </td>
                            <td>
                                @if($item->task)
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <a href="{{ route('tasks.show', $item->task) }}" style="font-weight: 500; text-decoration: none;">
                                            {{ $item->task->task_no ?? ('Task #'.$item->task->id) }}
                                        </a>
                                        <span class="badge {{ $item->task->status === 'completed' ? 'badge-success' : ($item->task->status === 'in_progress' ? 'badge-primary' : 'badge-secondary') }}">
                                            {{ ucwords(str_replace('_', ' ', $item->task->status)) }}
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
                                        <form action="{{ route('meetings.action-items.tasks.unlink', [$meeting, $item]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Unlink this task from the action item?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-warning">Unlink</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('meetings.action-items.destroy', [$meeting, $item]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this action item?')">
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
<div class="modal-overlay" id="addActionItemModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Action Item</h3>
            <button type="button" class="modal-close" onclick="closeModal('addActionItemModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('meetings.action-items.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="action_no" class="form-label">Action # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="action_no" id="action_no" class="form-input" value="{{ old('action_no', $meeting->actionItems->count() + 1) }}" min="1" required>
                </div>
                <div class="form-group">
                    <label for="title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required>
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-textarea" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="form-group">
                    <label for="assigned_to" class="form-label">Assigned To</label>
                    <select name="assigned_to" id="assigned_to" class="form-select">
                        <option value="">Select Assignee</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="assigned_department_id" class="form-label">Department</label>
                    <select name="assigned_department_id" id="assigned_department_id" class="form-select">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('assigned_department_id') == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="priority" class="form-label">Priority <span style="color: var(--danger);">*</span></label>
                    <select name="priority" id="priority" class="form-select">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="date" name="due_date" id="due_date" class="form-input" value="{{ old('due_date') }}">
                </div>
                <div class="form-group">
                    <label for="status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="status" id="status" class="form-select">
                        <option value="open" {{ old('status') === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addActionItemModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Action Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Action Item Modal -->
<div class="modal-overlay" id="editActionItemModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Action Item</h3>
            <button type="button" class="modal-close" onclick="closeModal('editActionItemModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="editActionItemForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_action_no" class="form-label">Action # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="action_no" id="edit_action_no" class="form-input" min="1" required>
                </div>
                <div class="form-group">
                    <label for="edit_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="edit_title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_description" class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_assigned_to" class="form-label">Assigned To</label>
                    <select name="assigned_to" id="edit_assigned_to" class="form-select">
                        <option value="">Select Assignee</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_assigned_department_id" class="form-label">Department</label>
                    <select name="assigned_department_id" id="edit_assigned_department_id" class="form-select">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_priority" class="form-label">Priority <span style="color: var(--danger);">*</span></label>
                    <select name="priority" id="edit_priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_due_date" class="form-label">Due Date</label>
                    <input type="date" name="due_date" id="edit_due_date" class="form-input">
                </div>
                <div class="form-group">
                    <label for="edit_status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="edit_remarks" class="form-textarea" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editActionItemModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Action Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Task Modal -->
<div class="modal-overlay" id="createTaskModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Create Task from Action Item</h3>
            <button type="button" class="modal-close" onclick="closeModal('createTaskModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="createTaskForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="task_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="task_title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="task_description" class="form-label">Description</label>
                    <textarea name="description" id="task_description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="task_priority" class="form-label">Priority <span style="color: var(--danger);">*</span></label>
                    <select name="priority" id="task_priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="task_status" class="form-label">Status <span style="color: var(--danger);">*</span></label>
                    <select name="status" id="task_status" class="form-select">
                        <option value="pending" selected>Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="task_due_date" class="form-label">Due Date</label>
                    <input type="date" name="due_date" id="task_due_date" class="form-input">
                </div>
                <div class="form-group">
                    <label for="task_responsible_user_id" class="form-label">Responsible User</label>
                    <select name="responsible_user_id" id="task_responsible_user_id" class="form-select">
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createTaskModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Link Task Modal -->
<div class="modal-overlay" id="linkTaskModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Link Existing Task</h3>
            <button type="button" class="modal-close" onclick="closeModal('linkTaskModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="linkTaskForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="link_task_id" class="form-label">Select Task <span style="color: var(--danger);">*</span></label>
                    <select name="task_id" id="link_task_id" class="form-select" required>
                        <option value="">Select a task</option>
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}">
                                {{ $task->task_no ?? 'Task #'.$task->id }} - {{ $task->title }} ({{ ucfirst($task->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('linkTaskModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Link Task</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Participants</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addParticipantModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->participants->isNotEmpty())
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Attendance</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->participants as $participant)
                        <tr>
                            <td>{{ $participant->user->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ ucwords($participant->participant_type) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $participant->attendance_status === 'present' ? 'badge-success' : ($participant->attendance_status === 'accepted' ? 'badge-primary' : 'badge-secondary') }}">
                                    {{ ucwords($participant->attendance_status) }}
                                </span>
                            </td>
                            <td>{{ $participant->remarks ?? 'N/A' }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditParticipantModal({{ $participant->id }}, '{{ $participant->user_id ?? '' }}', '{{ $participant->participant_type }}', '{{ $participant->attendance_status }}', '{{ addslashes($participant->remarks ?? '') }}')">Edit</button>
                                    <form action="{{ route('meetings.participants.destroy', [$meeting, $participant]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Remove this participant?')">
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
<div class="modal-overlay" id="addParticipantModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Participant</h3>
            <button type="button" class="modal-close" onclick="closeModal('addParticipantModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('meetings.participants.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="user_id" class="form-label">User <span style="color: var(--danger);">*</span></label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="participant_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="participant_type" id="participant_type" class="form-select">
                        <option value="organizer" {{ old('participant_type') === 'organizer' ? 'selected' : '' }}>Organizer</option>
                        <option value="chairperson" {{ old('participant_type') === 'chairperson' ? 'selected' : '' }}>Chairperson</option>
                        <option value="member" {{ old('participant_type') === 'member' ? 'selected' : '' }}>Member</option>
                        <option value="guest" {{ old('participant_type') === 'guest' ? 'selected' : '' }}>Guest</option>
                        <option value="presenter" {{ old('participant_type') === 'presenter' ? 'selected' : '' }}>Presenter</option>
                        <option value="observer" {{ old('participant_type') === 'observer' ? 'selected' : '' }}>Observer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="attendance_status" class="form-label">Attendance <span style="color: var(--danger);">*</span></label>
                    <select name="attendance_status" id="attendance_status" class="form-select">
                        <option value="invited" {{ old('attendance_status') === 'invited' ? 'selected' : '' }}>Invited</option>
                        <option value="accepted" {{ old('attendance_status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="declined" {{ old('attendance_status') === 'declined' ? 'selected' : '' }}>Declined</option>
                        <option value="present" {{ old('attendance_status') === 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ old('attendance_status') === 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="apology" {{ old('attendance_status') === 'apology' ? 'selected' : '' }}>Apology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addParticipantModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Participant</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Participant Modal -->
<div class="modal-overlay" id="editParticipantModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Participant</h3>
            <button type="button" class="modal-close" onclick="closeModal('editParticipantModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="editParticipantForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_user_id" class="form-label">User <span style="color: var(--danger);">*</span></label>
                    <select name="user_id" id="edit_user_id" class="form-select" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_participant_type" class="form-label">Type <span style="color: var(--danger);">*</span></label>
                    <select name="participant_type" id="edit_participant_type" class="form-select">
                        <option value="organizer">Organizer</option>
                        <option value="chairperson">Chairperson</option>
                        <option value="member">Member</option>
                        <option value="guest">Guest</option>
                        <option value="presenter">Presenter</option>
                        <option value="observer">Observer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_attendance_status" class="form-label">Attendance <span style="color: var(--danger);">*</span></label>
                    <select name="attendance_status" id="edit_attendance_status" class="form-select">
                        <option value="invited">Invited</option>
                        <option value="accepted">Accepted</option>
                        <option value="declined">Declined</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="apology">Apology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_remarks" class="form-label">Remarks</label>
                    <textarea name="remarks" id="edit_remarks" class="form-textarea" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editParticipantModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Participant</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Attachments</h2>
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal('addAttachmentModal')">Add</button>
    </div>
    <div class="card-body">
        @if($meeting->attachments->isNotEmpty())
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Description</th>
                            <th>Actions</th>
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
                                <form action="{{ route('meetings.attachments.destroy', [$meeting, $attachment]) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this attachment?')">
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
<div class="modal-overlay" id="addAttachmentModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Attachment</h3>
            <button type="button" class="modal-close" onclick="closeModal('addAttachmentModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('meetings.attachments.store', $meeting) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="file" class="form-label">File <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="file" id="file" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-textarea" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAttachmentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload Attachment</button>
            </div>
        </form>
    </div>
</div>

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
<div class="modal-overlay" id="addAgendaModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add Agenda Item</h3>
            <button type="button" class="modal-close" onclick="closeModal('addAgendaModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('meetings.agendas.store', $meeting) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="agenda_no" class="form-label">Agenda # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="agenda_no" id="agenda_no" class="form-input" value="{{ old('agenda_no', $meeting->agendas->count() + 1) }}" min="1" required>
                </div>
                <div class="form-group">
                    <label for="title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required>
                </div>
                <div class="form-group">
                    <label for="presented_by" class="form-label">Presented By</label>
                    <select name="presented_by" id="presented_by" class="form-select">
                        <option value="">Select Presenter</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('presented_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="estimated_minutes" class="form-label">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" id="estimated_minutes" class="form-input" value="{{ old('estimated_minutes') }}" min="1">
                </div>
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="skipped" {{ old('status') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-input" value="{{ old('sort_order', 0) }}" min="0">
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-textarea" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAgendaModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Agenda</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Agenda Modal -->
<div class="modal-overlay" id="editAgendaModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Agenda Item</h3>
            <button type="button" class="modal-close" onclick="closeModal('editAgendaModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="editAgendaForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_agenda_no" class="form-label">Agenda # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="agenda_no" id="edit_agenda_no" class="form-input" min="1" required>
                </div>
                <div class="form-group">
                    <label for="edit_title" class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" id="edit_title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_presented_by" class="form-label">Presented By</label>
                    <select name="presented_by" id="edit_presented_by" class="form-select">
                        <option value="">Select Presenter</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_estimated_minutes" class="form-label">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" id="edit_estimated_minutes" class="form-input" min="1">
                </div>
                <div class="form-group">
                    <label for="edit_status" class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="skipped">Skipped</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_sort_order" class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" id="edit_sort_order" class="form-input" min="0">
                </div>
                <div class="form-group">
                    <label for="edit_description" class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-textarea" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editAgendaModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Agenda</button>
            </div>
        </form>
    </div>
</div>

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
    function openEditAgendaModal(id, title, description, presentedBy, estimatedMinutes, status, sortOrder, agendaNo) {
        document.getElementById('editAgendaForm').action = '/meetings/{{ $meeting->id }}/agendas/' + id;
        document.getElementById('edit_agenda_no').value = agendaNo;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_presented_by').value = presentedBy;
        document.getElementById('edit_estimated_minutes').value = estimatedMinutes;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_sort_order').value = sortOrder;
        openModal('editAgendaModal');
    }

    function openEditDecisionModal(id, title, description, type, status, decisionDate, approvedBy, effectiveDate, remarks, decisionNo) {
        document.getElementById('editDecisionForm').action = '/meetings/{{ $meeting->id }}/decisions/' + id;
        document.getElementById('edit_decision_no').value = decisionNo;
        document.getElementById('edit_decision_title').value = title;
        document.getElementById('edit_decision_description').value = description;
        document.getElementById('edit_decision_type').value = type;
        document.getElementById('edit_decision_status').value = status;
        document.getElementById('edit_decision_date').value = decisionDate;
        document.getElementById('edit_approved_by').value = approvedBy;
        document.getElementById('edit_effective_date').value = effectiveDate;
        document.getElementById('edit_remarks').value = remarks;
        openModal('editDecisionModal');
    }

    function openEditActionItemModal(id, title, description, assignedTo, assignedDepartment, priority, dueDate, status, actionNo) {
        document.getElementById('editActionItemForm').action = '/meetings/{{ $meeting->id }}/action-items/' + id;
        document.getElementById('edit_action_no').value = actionNo;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_assigned_to').value = assignedTo;
        document.getElementById('edit_assigned_department_id').value = assignedDepartment;
        document.getElementById('edit_priority').value = priority;
        document.getElementById('edit_due_date').value = dueDate;
        document.getElementById('edit_status').value = status;
        openModal('editActionItemModal');
    }

    function openEditParticipantModal(id, userId, participantType, attendanceStatus, remarks) {
        document.getElementById('editParticipantForm').action = '/meetings/{{ $meeting->id }}/participants/' + id;
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_participant_type').value = participantType;
        document.getElementById('edit_attendance_status').value = attendanceStatus;
        document.getElementById('edit_remarks').value = remarks;
        openModal('editParticipantModal');
    }

    function openCreateTaskModal(actionItemId, title, description, dueDate, assignedTo) {
        document.getElementById('createTaskForm').action = '/meetings/{{ $meeting->id }}/action-items/' + actionItemId + '/tasks';
        document.getElementById('task_title').value = title || '';
        document.getElementById('task_description').value = description || '';
        document.getElementById('task_priority').value = 'medium';
        document.getElementById('task_status').value = 'pending';
        document.getElementById('task_due_date').value = dueDate || '';
        document.getElementById('task_responsible_user_id').value = assignedTo || '';
        openModal('createTaskModal');
    }

    function openLinkTaskModal(actionItemId) {
        document.getElementById('linkTaskForm').action = '/meetings/{{ $meeting->id }}/action-items/' + actionItemId + '/tasks/link';
        document.getElementById('link_task_id').value = '';
        openModal('linkTaskModal');
    }
</script>
@endpush
@endsection
