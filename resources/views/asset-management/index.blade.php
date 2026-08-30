@extends('tyro-dashboard::layouts.app')

@section('title', 'Asset Dashboard')

@section('breadcrumb')
<span>Asset Dashboard</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Asset Dashboard</h1>
            <p class="page-description">Complete lifecycle overview: Procurement &rarr; Assignment &rarr; Usage &rarr; Maintenance &rarr; Audit &rarr; Disposal.</p>
        </div>
    </div>
</div>

<div class="stats-grid">
    @include('partials._stat-card', ['title' => 'Total Assets', 'value' => $stats['total_assets'], 'icon' => 'clipboard'])
    @include('partials._stat-card', ['title' => 'In Stock', 'value' => $stats['available'], 'icon' => 'check-circle', 'style' => 'success'])
    @include('partials._stat-card', ['title' => 'Assigned', 'value' => $stats['assigned'], 'icon' => 'clock', 'style' => 'info'])
    @include('partials._stat-card', ['title' => 'Under Repair', 'value' => $stats['in_repair'], 'icon' => 'clock', 'style' => 'warning'])
    @include('partials._stat-card', ['title' => 'Open Maintenance', 'value' => $stats['open_maintenance'], 'icon' => 'clock', 'style' => 'warning'])
    @include('partials._stat-card', ['title' => 'Pending POs', 'value' => $stats['pending_pos'], 'icon' => 'clipboard', 'style' => 'primary'])
</div>

<div class="stats-grid" style="margin-top: 1.5rem;">
    @include('partials._stat-card', ['title' => 'Employees', 'value' => $stats['employees'], 'icon' => 'clipboard'])
    @include('partials._stat-card', ['title' => 'Vendors', 'value' => $stats['vendors'], 'icon' => 'clipboard'])
    @include('partials._stat-card', ['title' => 'Departments', 'value' => $stats['departments'], 'icon' => 'clipboard'])
    @include('partials._stat-card', ['title' => 'Locations', 'value' => $stats['locations'], 'icon' => 'clipboard'])
    @include('partials._stat-card', ['title' => 'Active Transfers', 'value' => $stats['active_transfers'], 'icon' => 'clipboard'])
    @include('partials._stat-card', ['title' => 'Needs Audit', 'value' => $needsAudit, 'icon' => 'clock', 'style' => 'danger'])
</div>

<div class="grid-2" style="margin-top: 1.5rem;">
    @include('partials._detail-card', [
        'title' => 'Recently Added Assets',
        'items' => $recentAssetItems,
        'emptyMessage' => 'No assets added yet.',
        'viewAllRoute' => $assetsIndexRoute,
        'viewAllLabel' => 'View Assets',
    ])
    @include('partials._detail-card', [
        'title' => 'Warranty Expiring (60 Days)',
        'items' => $warrantyItems,
        'emptyMessage' => 'No warranties expiring soon.',
        'viewAllRoute' => $assetsIndexRoute,
        'viewAllLabel' => 'View Assets',
    ])
    @include('partials._detail-card', [
        'title' => 'Open Maintenance Requests',
        'items' => $maintenanceItems,
        'emptyMessage' => 'No open maintenance requests.',
        'viewAllRoute' => $maintenanceIndexRoute,
        'viewAllLabel' => 'View Requests',
    ])
    @include('partials._detail-card', [
        'title' => 'Recent Disposals',
        'items' => $disposalItems,
        'emptyMessage' => 'No disposals recorded.',
        'viewAllRoute' => $disposalIndexRoute,
        'viewAllLabel' => 'View Disposals',
    ])
</div>
@endsection
