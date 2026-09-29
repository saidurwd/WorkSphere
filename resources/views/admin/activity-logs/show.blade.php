@extends('layouts.app')

@section('title', 'Activity Log: ' . $activityLog->id)

@section('content')
    <x-page-header title="Activity Log #{{ $activityLog->id }}" subtitle="Detailed activity record." icon="journal-text" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Activity Details</h3>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-body-secondary">When</div>
                    <div class="fw-semibold">{{ $activityLog->created_at->format('l, F j, Y H:i') }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">User</div>
                    <div class="fw-semibold">
                        @if ($activityLog->user)
                            <x-user-cell :name="$activityLog->user->name" :email="$activityLog->user->email" :size="28" />
                        @else
                            <span class="text-body-secondary">System</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Module</div>
                    <div class="fw-semibold">{{ $activityLog->module_name ?? '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Action</div>
                    <div class="fw-semibold">{{ $activityLog->action }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Record ID</div>
                    <div class="fw-semibold">{{ $activityLog->record_id ?? '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">IP Address</div>
                    <div class="fw-semibold">{{ $activityLog->ip_address ?? '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">Old Value</div>
                    <pre class="p-3 bg-body-tertiary rounded border mb-0" style="white-space: pre-wrap;">{{ $activityLog->old_value ? json_encode($activityLog->old_value, JSON_PRETTY_PRINT) : '—' }}</pre>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">New Value</div>
                    <pre class="p-3 bg-body-tertiary rounded border mb-0" style="white-space: pre-wrap;">{{ $activityLog->new_value ? json_encode($activityLog->new_value, JSON_PRETTY_PRINT) : '—' }}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection
