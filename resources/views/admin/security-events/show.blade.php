@extends('layouts.app')

@section('title', 'Security Event: ' . $securityEvent->id)

@section('content')
    <x-page-header title="Security Event #{{ $securityEvent->id }}" subtitle="Detailed security event record." icon="shield-exclamation" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Event Details</h3>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-body-secondary">When</div>
                    <div class="fw-semibold">{{ $securityEvent->attempted_at->format('l, F j, Y H:i') }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">User</div>
                    <div class="fw-semibold">
                        @if ($securityEvent->user)
                            <x-user-cell :name="$securityEvent->user->name" :email="$securityEvent->user->email" :size="28" />
                        @else
                            <span class="text-body-secondary">{{ $securityEvent->email }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Event</div>
                    <div class="fw-semibold">
                        <x-badge :variant="$securityEvent->eventVariant()">{{ $securityEvent->eventLabel() }}</x-badge>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Device</div>
                    <div class="fw-semibold">{{ $securityEvent->device ?? '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">IP Address</div>
                    <div class="fw-semibold">{{ $securityEvent->ip_address ?? '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">User Agent</div>
                    <div class="fw-semibold" style="word-break: break-word;">{{ $securityEvent->user_agent ?? '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">Failure Reason</div>
                    <div class="fw-semibold">{{ $securityEvent->failure_reason ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
