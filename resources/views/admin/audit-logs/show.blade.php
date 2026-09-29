@extends('layouts.app')

@section('title', 'Audit Log: ' . $auditLog->id)

@section('content')
    <x-page-header title="Audit Log #{{ $auditLog->id }}" subtitle="Detailed audit record." icon="shield-check" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Audit Details</h3>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-body-secondary">When</div>
                    <div class="fw-semibold">{{ $auditLog->created_at->format('l, F j, Y H:i') }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">User</div>
                    <div class="fw-semibold">
                        @if ($auditLog->user)
                            <x-user-cell :name="$auditLog->user->name" :email="$auditLog->user->email" :size="28" />
                        @else
                            <span class="text-body-secondary">System</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Event</div>
                    <div class="fw-semibold">{{ $auditLog->event }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Subject</div>
                    <div class="fw-semibold">{{ $auditLog->subjectLabel() }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">Old Values</div>
                    <pre class="p-3 bg-body-tertiary rounded border mb-0" style="white-space: pre-wrap;">{{ $auditLog->old_values ? json_encode($auditLog->old_values, JSON_PRETTY_PRINT) : '—' }}</pre>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">New Values</div>
                    <pre class="p-3 bg-body-tertiary rounded border mb-0" style="white-space: pre-wrap;">{{ $auditLog->new_values ? json_encode($auditLog->new_values, JSON_PRETTY_PRINT) : '—' }}</pre>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">Metadata</div>
                    <pre class="p-3 bg-body-tertiary rounded border mb-0" style="white-space: pre-wrap;">{{ $auditLog->metadata ? json_encode($auditLog->metadata, JSON_PRETTY_PRINT) : '—' }}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection
