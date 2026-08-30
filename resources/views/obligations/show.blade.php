@extends('tyro-dashboard::layouts.admin')

@section('title', $obligation->obligation_no.' - '.$obligation->title)

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.index') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $obligation->obligation_no }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $obligation->obligation_no }}</h1>
            <p class="page-description">{{ $obligation->title }}</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="{{ route('obligations.edit', $obligation) }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </a>
            <a href="{{ route('obligations.renew.create', $obligation) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Renew
            </a>
        </div>
    </div>
</div>

@php
    $remaining = now()->startOfDay()->diffInDays($obligation->expiry_date, false);
    $riskBadge = match ($obligation->risk_level) {
        'critical' => 'badge-danger',
        'high' => 'badge-warning',
        'medium' => 'badge-primary',
        'low' => 'badge-secondary',
    };
    $priorityBadge = match ($obligation->priority) {
        'critical' => 'badge-danger',
        'high' => 'badge-warning',
        'medium' => 'badge-primary',
        'low' => 'badge-secondary',
    };
@endphp

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Basic Information</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Obligation No.</span>
                    <strong>{{ $obligation->obligation_no }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Title</span>
                    <strong>{{ $obligation->title }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Type</span>
                    <strong>{{ $obligation->type->type_name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Category</span>
                    <strong>{{ $obligation->category->category_name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Company</span>
                    <strong>{{ $obligation->company->company_name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Department</span>
                    <strong>{{ $obligation->department->department_name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Location</span>
                    <strong>{{ $obligation->location->location_name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Vendor</span>
                    <strong>{{ $obligation->vendor->vendor_name ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Ownership & Dates</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Owner</span>
                    <strong>{{ $obligation->owner->name ?? 'Unassigned' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Backup Owner</span>
                    <strong>{{ $obligation->backupUser->name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Reviewer</span>
                    <strong>{{ $obligation->reviewer->name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Approver</span>
                    <strong>{{ $obligation->approver->name ?? 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Start Date</span>
                    <strong>{{ $obligation->start_date->format('Y-m-d') }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Expiry Date</span>
                    <strong>{{ $obligation->expiry_date->format('Y-m-d') }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Days Remaining</span>
                    <strong style="color: {{ $remaining < 0 ? 'var(--destructive)' : 'inherit' }};">
                        {{ $remaining < 0 ? 'Expired '.abs($remaining).' days ago' : $remaining.' days' }}
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Status & Priority</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Status</span>
                    <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $obligation->status)) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Priority</span>
                    <span class="badge {{ $priorityBadge }}">{{ ucfirst($obligation->priority) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Risk Level</span>
                    <span class="badge {{ $riskBadge }}">{{ ucfirst($obligation->risk_level) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Renewal Required</span>
                    <strong>{{ $obligation->renewal_required ? 'Yes' : 'No' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Auto Renew</span>
                    <strong>{{ $obligation->auto_renew ? 'Yes' : 'No' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Recurrence</span>
                    <strong>{{ ucfirst($obligation->recurrence_type ?? 'None') }} ({{ $obligation->recurrence_interval ?? '-' }})</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Financial Information</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Estimated Cost</span>
                    <strong>{{ $obligation->estimated_cost ? number_format($obligation->estimated_cost, 2).' '.$obligation->currency : 'N/A' }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted-foreground);">Created By</span>
                    <strong>{{ $obligation->creator->name ?? 'N/A' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

@if($obligation->notes)
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Notes</h3>
    </div>
    <div class="card-body">
        <p style="white-space: pre-wrap;">{{ $obligation->notes }}</p>
    </div>
</div>
@endif

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Documents</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('obligations.documents.store', $obligation) }}" method="POST" enctype="multipart/form-data" style="margin-bottom: 1rem;">
            @csrf
            <div style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Document Type</label>
                    <select name="document_type" class="form-select">
                        <option value="CONTRACT">Contract</option>
                        <option value="LICENSE">License</option>
                        <option value="CERTIFICATE">Certificate</option>
                        <option value="PURCHASE_ORDER">Purchase Order</option>
                        <option value="INVOICE">Invoice</option>
                        <option value="QUOTATION">Quotation</option>
                        <option value="RENEWAL_CERTIFICATE">Renewal Certificate</option>
                        <option value="AGREEMENT">Agreement</option>
                        <option value="APPROVAL_DOCUMENT">Approval Document</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">File</label>
                    <input type="file" name="file" class="form-input" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Document Date</label>
                    <input type="date" name="document_date" class="form-input">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-input">
                </div>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>

        @if($obligation->documents->count())
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($obligation->documents as $document)
                    <tr>
                        <td>{{ $document->document_type }}</td>
                        <td>{{ $document->file_name }}</td>
                        <td>{{ $document->uploader->name ?? 'N/A' }}</td>
                        <td>{{ $document->created_at->format('M d, Y') }}</td>
                        <td style="text-align: right;">
                            <a href="{{ Storage::url($document->file_path) }}" class="action-btn" title="Download" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                            <form action="{{ route('obligations.documents.destroy', [$obligation, $document]) }}" method="POST" style="display: inline;" id="delete-doc-{{ $document->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Delete this document?')) { document.getElementById('delete-doc-{{ $document->id }}').submit(); }">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: var(--muted-foreground);">No documents uploaded.</p>
        @endif
    </div>
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Renewal History</h3>
        </div>
        <div class="card-body">
            @if($obligation->renewals->count())
                <table class="table">
                    <thead>
                        <tr>
                            <th>Renewal Date</th>
                            <th>Previous Expiry</th>
                            <th>New Expiry</th>
                            <th>Cost</th>
                            <th>Renewed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($obligation->renewals as $renewal)
                        <tr>
                            <td>{{ $renewal->renewal_date->format('M d, Y') }}</td>
                            <td>{{ $renewal->previous_expiry_date->format('M d, Y') }}</td>
                            <td>{{ $renewal->new_expiry_date->format('M d, Y') }}</td>
                            <td>{{ $renewal->cost ? number_format($renewal->cost, 2).' '.$renewal->currency : 'N/A' }}</td>
                            <td>{{ $renewal->renewedBy->name ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: var(--muted-foreground);">No renewal history.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Responsibilities</h3>
        </div>
        <div class="card-body">
            @if($obligation->responsibilities->count())
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Type</th>
                            <th>Escalation Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($obligation->responsibilities as $responsibility)
                        <tr>
                            <td>{{ $responsibility->user->name ?? 'N/A' }}</td>
                            <td>{{ $responsibility->responsibility_type }}</td>
                            <td>{{ $responsibility->escalation_level ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: var(--muted-foreground);">No additional responsibilities.</p>
            @endif
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Activity Timeline</h3>
    </div>
    <div class="card-body">
        @if($obligation->activityLogs->count())
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($obligation->activityLogs as $log)
                <div style="display: flex; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                    <div style="min-width: 120px; color: var(--muted-foreground); font-size: 0.875rem;">
                        {{ $log->created_at->format('M d, Y H:i') }}
                    </div>
                    <div>
                        <div style="font-weight: 600;">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</div>
                        <div style="font-size: 0.875rem; color: var(--muted-foreground);">by {{ $log->user->name ?? 'System' }}</div>
                        @if($log->remarks)
                            <div style="font-size: 0.875rem; margin-top: 0.25rem;">{{ $log->remarks }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <p style="color: var(--muted-foreground);">No activity recorded.</p>
        @endif
    </div>
</div>
@endsection
