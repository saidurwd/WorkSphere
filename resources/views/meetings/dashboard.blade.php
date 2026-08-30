@extends('tyro-dashboard::layouts.admin')

@section('title', 'Meeting Dashboard')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Meeting Dashboard</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Meeting Dashboard</h1>
            <p class="page-description">Overview of meetings and action items.</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-body" style="padding: 1rem 1.25rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Meetings This Month</div>
            <div style="margin-top: 0.35rem; font-size: 1.5rem; font-weight: 700; color: var(--card-foreground);">{{ $stats['this_month'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding: 1rem 1.25rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Upcoming Meetings</div>
            <div style="margin-top: 0.35rem; font-size: 1.5rem; font-weight: 700; color: var(--card-foreground);">{{ $stats['upcoming'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding: 1rem 1.25rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Completed Meetings</div>
            <div style="margin-top: 0.35rem; font-size: 1.5rem; font-weight: 700; color: var(--card-foreground);">{{ $stats['completed'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding: 1rem 1.25rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Pending Actions</div>
            <div style="margin-top: 0.35rem; font-size: 1.5rem; font-weight: 700; color: var(--card-foreground);">{{ $stats['pending_actions'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding: 1rem 1.25rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Overdue Actions</div>
            <div style="margin-top: 0.35rem; font-size: 1.5rem; font-weight: 700; color: var(--danger);">{{ $stats['overdue_actions'] }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding: 1rem 1.25rem;">
            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground); font-weight: 600;">Awaiting Approval</div>
            <div style="margin-top: 0.35rem; font-size: 1.5rem; font-weight: 700; color: var(--warning);">{{ $stats['awaiting_approval'] }}</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Upcoming Meetings</h2>
        </div>
        <div class="card-body">
            @if($upcomingMeetings->isNotEmpty())
                @foreach($upcomingMeetings as $meeting)
                <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                    <a href="{{ route('meetings.show', $meeting) }}" style="font-weight: 600; text-decoration: none; color: inherit;">{{ $meeting->title }}</a>
                    <div style="font-size: 0.85rem; color: var(--muted-foreground);">{{ $meeting->meeting_date->format('M d, Y') }} &middot; {{ $meeting->start_time->format('H:i') }}</div>
                </div>
                @endforeach
            @else
                <p style="color: var(--muted-foreground);">No upcoming meetings.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">My Pending Actions</h2>
        </div>
        <div class="card-body">
            @if($myPendingActions->isNotEmpty())
                @foreach($myPendingActions as $action)
                <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                    <div style="font-weight: 600;">{{ $action->title }}</div>
                    <div style="font-size: 0.85rem; color: var(--muted-foreground);">Due {{ $action->due_date->format('M d, Y') }}</div>
                </div>
                @endforeach
            @else
                <p style="color: var(--muted-foreground);">No pending actions.</p>
            @endif
        </div>
    </div>
</div>
@endsection
