@extends('tyro-dashboard::layouts.admin')

@section('title', 'Meeting Reports')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<span>Reports</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Meeting Reports</h1>
            <p class="page-description">Analyze meetings, actions, and decisions.</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <a href="{{ route('meetings.reports.meetings') }}" class="card" style="text-decoration: none; color: inherit; transition: transform 0.15s ease;">
        <div class="card-body" style="padding: 1.5rem;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">Meeting Summary</div>
            <div style="color: var(--muted-foreground); font-size: 0.9rem;">View meeting history and status.</div>
        </div>
    </a>
    <a href="{{ route('meetings.reports.actions') }}" class="card" style="text-decoration: none; color: inherit; transition: transform 0.15s ease;">
        <div class="card-body" style="padding: 1.5rem;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">Action Items</div>
            <div style="color: var(--muted-foreground); font-size: 0.9rem;">Track action item progress.</div>
        </div>
    </a>
    <a href="{{ route('meetings.reports.overdue') }}" class="card" style="text-decoration: none; color: inherit; transition: transform 0.15s ease;">
        <div class="card-body" style="padding: 1.5rem;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">Overdue Actions</div>
            <div style="color: var(--muted-foreground); font-size: 0.9rem;">Identify overdue action items.</div>
        </div>
    </a>
    <a href="{{ route('meetings.reports.person-wise') }}" class="card" style="text-decoration: none; color: inherit; transition: transform 0.15s ease;">
        <div class="card-body" style="padding: 1.5rem;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">Person-wise Accountability</div>
            <div style="color: var(--muted-foreground); font-size: 0.9rem;">View accountability by person.</div>
        </div>
    </a>
    <a href="{{ route('meetings.reports.department-wise') }}" class="card" style="text-decoration: none; color: inherit; transition: transform 0.15s ease;">
        <div class="card-body" style="padding: 1.5rem;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">Department Performance</div>
            <div style="color: var(--muted-foreground); font-size: 0.9rem;">View performance by department.</div>
        </div>
    </a>
    <a href="{{ route('meetings.reports.decisions') }}" class="card" style="text-decoration: none; color: inherit; transition: transform 0.15s ease;">
        <div class="card-body" style="padding: 1.5rem;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem;">Decision Register</div>
            <div style="color: var(--muted-foreground); font-size: 0.9rem;">Browse meeting decisions.</div>
        </div>
    </a>
</div>
@endsection
