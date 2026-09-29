@extends('layouts.app')

@section('title', 'Login Log: ' . $loginLog->id)

@section('content')
    <x-page-header title="Login Log #{{ $loginLog->id }}" subtitle="Detailed authentication record." icon="clock-history" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Login Details</h3>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-body-secondary">When</div>
                    <div class="fw-semibold">{{ $loginLog->attempted_at->format('l, F j, Y H:i') }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">User</div>
                    <div class="fw-semibold">
                        @if ($loginLog->user)
                            <x-user-cell :name="$loginLog->user->name" :email="$loginLog->user->email" :size="28" />
                        @else
                            <span class="text-body-secondary">{{ $loginLog->email }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Event</div>
                    <div class="fw-semibold">
                        <x-badge :variant="$loginLog->eventVariant()">{{ $loginLog->eventLabel() }}</x-badge>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">Device</div>
                    <div class="fw-semibold">{{ $loginLog->device ?? '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">IP Address</div>
                    <div class="fw-semibold">{{ $loginLog->ip_address ?? '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-body-secondary">User Agent</div>
                    <div class="fw-semibold" style="word-break: break-word;">{{ $loginLog->user_agent ?? '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">Failure Reason</div>
                    <div class="fw-semibold">{{ $loginLog->failure_reason ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
