@extends('layouts.app')

@section('title', 'Obligations Dashboard')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Obligations'],
    ];
@endphp

@section('content')
<x-page-header title="Obligations Dashboard" subtitle="Overview of compliance obligations and renewals.">
    <x-btn :href="route('obligations.index')" variant="secondary">View All Obligations</x-btn>
</x-page-header>

<div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
    <x-stat class="col" label="Active Obligations" value="{{ $active }}" variant="primary" />
    <x-stat class="col" label="Due Within 7 Days" value="{{ $dueWithin7Days }}" variant="warning" />
    <x-stat class="col" label="Due Within 30 Days" value="{{ $dueWithin30Days }}" variant="info" />
    <x-stat class="col" label="Expired" value="{{ $expired }}" variant="destructive" />
    <x-stat class="col" label="Critical Risk" value="{{ $critical }}" variant="destructive" />
    <x-stat class="col" label="High Risk" value="{{ $highRisk }}" variant="warning" />
    <x-stat class="col" label="Renewal In Progress" value="{{ $renewalInProgress }}" variant="info" />
    <x-stat class="col" label="Pending Approval" value="{{ $pendingApproval }}" variant="primary" />
    <x-stat class="col" label="Overdue Tasks" value="{{ $overdueTasks }}" variant="destructive" />
</div>

<div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
    <div class="col card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Obligations by Type</h3>
            <span class="badge text-bg-secondary">Horizontal bars</span>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction: column; gap: 0.875rem;">
                @foreach($typeBars as $row)
                    <div>
                        <div style="display:flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;">
                            <div style="font-weight: 600;">{{ $row['label'] }}</div>
                            <div style="font-size: 0.875rem; color: var(--muted-foreground);">{{ $row['value'] }}</div>
                        </div>
                        <div style="height: 12px; width: 100%; background: var(--muted); border-radius: 9999px; overflow:hidden; border: 1px solid var(--border);">
                            <div style="height: 100%; width: {{ $row['pct'] }}%; background: {{ $row['color'] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Priority Distribution</h3>
            <span class="badge text-bg-secondary">Donut chart</span>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 140px 1fr; gap: 1.25rem; align-items: center;">
                <div style="display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 42 42" width="132" height="132" style="display:block;">
                        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--border)" stroke-width="6"></circle>
                        @php($offset = 25)
                        @foreach($priorityDonut as $slice)
                            <circle
                                cx="21" cy="21" r="15.915"
                                fill="transparent"
                                stroke="currentColor"
                                stroke-width="6"
                                stroke-dasharray="{{ $slice['pct'] }} {{ 100 - $slice['pct'] }}"
                                stroke-dashoffset="{{ $offset }}"
                                stroke-linecap="round"
                                style="color: {{ $slice['color'] }};"
                            ></circle>
                            @php($offset -= $slice['pct'])
                        @endforeach
                    </svg>
                </div>
                <div>
                    <div style="display:flex; flex-direction:column; gap: 0.625rem;">
                        @foreach($priorityDonut as $slice)
                            <div style="display:flex; align-items:center; justify-content: space-between; gap: 1rem;">
                                <div style="display:flex; align-items:center; gap: 0.5rem; min-width: 0;">
                                    <span style="width: 10px; height: 10px; border-radius: 9999px; background: {{ $slice['color'] }}; display:inline-block;"></span>
                                    <span style="font-size: 0.9375rem; color: var(--foreground);">{{ $slice['label'] }}</span>
                                </div>
                                <div style="font-size: 0.9375rem; color: var(--muted-foreground);">{{ $slice['count'] }} ({{ $slice['pct'] }}%)</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); display:flex; justify-content: space-between;">
                        <span style="font-size: 0.875rem; color: var(--muted-foreground);">Total</span>
                        <strong style="font-size: 0.9375rem;">{{ $priorityTotal }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
    <div class="col card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Upcoming Obligations</h3>
        </div>
        <div class="card-body">
            @if(count($upcoming))
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Remaining</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcoming as $item)
                        <tr>
                            <td><a href="{{ $item['url'] }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $item['title'] }}</a></td>
                            <td>{{ $item['subtitle'] }}</td>
                            <td><span class="badge {{ $item['badge']['variant'] }}">{{ $item['badge']['text'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: var(--muted-foreground);">No upcoming obligations.</p>
            @endif
        </div>
    </div>

    <div class="col card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Critical Obligations</h3>
        </div>
        <div class="card-body">
            @if(count($criticalList))
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Remaining</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($criticalList as $item)
                        <tr>
                            <td><a href="{{ $item['url'] }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $item['title'] }}</a></td>
                            <td>{{ $item['subtitle'] }}</td>
                            <td><span class="badge {{ $item['badge']['variant'] }}">{{ $item['badge']['text'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: var(--muted-foreground);">No critical obligations.</p>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Expired Obligations</h3>
    </div>
    <div class="card-body">
            @if(count($expiredList))
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Remaining</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expiredList as $item)
                    <tr>
                        <td><a href="{{ $item['url'] }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $item['title'] }}</a></td>
                        <td>{{ $item['subtitle'] }}</td>
                        <td><span class="badge {{ $item['badge']['variant'] }}">{{ $item['badge']['text'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: var(--muted-foreground);">No expired obligations.</p>
        @endif
    </div>
</div>
@endsection
