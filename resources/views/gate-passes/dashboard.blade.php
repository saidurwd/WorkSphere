@extends('tyro-dashboard::layouts.app')

@section('title', 'Gate Pass Dashboard')

@section('breadcrumb')
<span>Gate Pass Dashboard</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Gate Pass Dashboard</h1>
            <p class="page-description">Overview of facility gate passes and issued passes.</p>
        </div>
        <div>
            <a href="{{ route('gate-passes.index') }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Manage Passes
            </a>
        </div>
    </div>
</div>

<div class="stats-grid">
    @include('partials._stat-card', ['title' => 'Total Passes', 'value' => $total, 'icon' => 'clipboard'])
    @include('partials._stat-card', ['title' => 'This Month', 'value' => $thisMonth, 'icon' => 'info', 'style' => 'info'])
    @include('partials._stat-card', ['title' => 'Checked', 'value' => $checked, 'icon' => 'check-circle', 'style' => 'success'])
    @include('partials._stat-card', ['title' => 'Pending Check', 'value' => $pendingCheck, 'icon' => 'warning', 'style' => 'warning'])
    @include('partials._stat-card', ['title' => 'Total Quantity', 'value' => $totalQuantity, 'icon' => 'clipboard', 'style' => 'primary'])
</div>

<div class="grid-2">
    @include('partials._detail-card', [
        'title' => "Today's Issued Passes",
        'items' => $todayPasses,
        'emptyMessage' => 'No passes issued today.',
        'viewAllRoute' => $viewAllTodayRoute,
        'viewAllLabel' => 'View Today',
    ])
    @include('partials._detail-card', [
        'title' => 'This Week',
        'items' => $thisWeekPasses,
        'emptyMessage' => 'No passes issued this week.',
        'viewAllRoute' => $viewAllWeekRoute,
        'viewAllLabel' => 'View This Week',
    ])
    @include('partials._detail-card', [
        'title' => 'This Month',
        'items' => $thisMonthPasses,
        'emptyMessage' => 'No passes issued this month.',
        'viewAllRoute' => $viewAllMonthRoute,
        'viewAllLabel' => 'View This Month',
    ])
    @include('partials._detail-card', [
        'title' => 'Pending Check',
        'items' => $pendingCheckPasses,
        'emptyMessage' => 'All passes have been checked.',
        'viewAllRoute' => $viewAllPendingRoute,
        'viewAllLabel' => 'View Pending',
    ])
</div>

<div class="grid-2" style="margin-top: 1.5rem;">
    @include('partials._detail-card', [
        'title' => 'Recently Prepared',
        'items' => $recentlyPrepared,
        'emptyMessage' => 'No prepared passes yet.',
        'viewAllRoute' => $viewAllPreparedRoute,
        'viewAllLabel' => 'View All',
    ])
</div>
@endsection
