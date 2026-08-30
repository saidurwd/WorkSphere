@extends('tyro-dashboard::layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
<span>Dashboard</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-description">Overview of {{ config('app.name') }}.</p>
        </div>
    </div>
</div>

<div class="stats-grid">
    <x-stat label="Total Tasks" value="{{ $total }}" variant="primary" />
    <x-stat label="Completed Tasks" value="{{ $completed }}" variant="success" />
    <x-stat label="Pending Tasks" value="{{ $pending }}" variant="warning" />
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Task Status</h3>
            <span class="badge badge-secondary">Donut chart</span>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 140px 1fr; gap: 1.25rem; align-items: center;">
                <div style="display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 42 42" width="132" height="132" style="display:block;">
                        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--border)" stroke-width="6"></circle>
                        @php($offset = 25)
                        @foreach($statusDonut as $slice)
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
                        @foreach($statusDonut as $slice)
                            <div style="display:flex; align-items:center; justify-content: space-between; gap: 1rem;">
                                <div style="display:flex; align-items:center; gap: 0.5rem; min-width: 0;">
                                    <span style="width: 10px; height: 10px; border-radius: 9999px; background: {{ $slice['color'] }}; display:inline-block;"></span>
                                    <span style="font-size: 0.9375rem; color: var(--foreground); white-space: nowrap; overflow:hidden; text-overflow: ellipsis;">{{ $slice['label'] }}</span>
                                </div>
                                <div style="font-size: 0.9375rem; color: var(--muted-foreground);">{{ $slice['count'] }} ({{ $slice['pct'] }}%)</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); display:flex; justify-content: space-between;">
                        <span style="font-size: 0.875rem; color: var(--muted-foreground);">Total</span>
                        <strong style="font-size: 0.9375rem;">{{ $statusTotal }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Weekly Tasks</h3>
            <span class="badge badge-secondary">Bar chart</span>
        </div>
        <div class="card-body">
            <div style="display:flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <div style="font-size: 0.875rem; color: var(--muted-foreground);">Created this week</div>
                    <div style="font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em;">{{ collect($weeklyBars)->sum('value') }}</div>
                </div>
            </div>
            <div style="border: 1px solid var(--border); border-radius: 10px; padding: 1rem; background: var(--muted);">
                <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 0.625rem; align-items: end; height: 180px;">
                    @foreach($weeklyBars as $bar)
                        <div style="display:flex; flex-direction: column; gap: 0.5rem; align-items: stretch;">
                            <div title="{{ $bar['label'] }}: {{ $bar['value'] }}" style="height: 150px; display:flex; align-items:flex-end; position: relative;">
                                <div style="position:absolute; top: 0; left: 0; right: 0; text-align:center; font-size: 0.75rem; color: var(--muted-foreground);">{{ $bar['value'] }}</div>
                                <div style="width: 100%; height: {{ $bar['pct'] }}%; background: var(--foreground); border-radius: 8px; border: 1px solid var(--border);"></div>
                            </div>
                            <div style="font-size: 0.8125rem; color: var(--muted-foreground); text-align:center;">{{ $bar['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Asset Status</h3>
            <span class="badge badge-secondary">Donut chart</span>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 140px 1fr; gap: 1.25rem; align-items: center;">
                <div style="display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 42 42" width="132" height="132" style="display:block;">
                        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--border)" stroke-width="6"></circle>
                        @php($offset = 25)
                        @foreach($assetStatusDonut as $slice)
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
                        @foreach($assetStatusDonut as $slice)
                            <div style="display:flex; align-items:center; justify-content: space-between; gap: 1rem;">
                                <div style="display:flex; align-items:center; gap: 0.5rem; min-width: 0;">
                                    <span style="width: 10px; height: 10px; border-radius: 9999px; background: {{ $slice['color'] }}; display:inline-block;"></span>
                                    <span style="font-size: 0.9375rem; color: var(--foreground); white-space: nowrap; overflow:hidden; text-overflow: ellipsis;">{{ $slice['label'] }}</span>
                                </div>
                                <div style="font-size: 0.9375rem; color: var(--muted-foreground);">{{ $slice['count'] }} ({{ $slice['pct'] }}%)</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); display:flex; justify-content: space-between;">
                        <span style="font-size: 0.875rem; color: var(--muted-foreground);">Total</span>
                        <strong style="font-size: 0.9375rem;">{{ $assetStatusTotal }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Gate Pass Status</h3>
            <span class="badge badge-secondary">Donut chart</span>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 140px 1fr; gap: 1.25rem; align-items: center;">
                <div style="display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 42 42" width="132" height="132" style="display:block;">
                        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--border)" stroke-width="6"></circle>
                        @php($offset = 25)
                        @foreach($gatePassStatusDonut as $slice)
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
                        @foreach($gatePassStatusDonut as $slice)
                            <div style="display:flex; align-items:center; justify-content: space-between; gap: 1rem;">
                                <div style="display:flex; align-items:center; gap: 0.5rem; min-width: 0;">
                                    <span style="width: 10px; height: 10px; border-radius: 9999px; background: {{ $slice['color'] }}; display:inline-block;"></span>
                                    <span style="font-size: 0.9375rem; color: var(--foreground); white-space: nowrap; overflow:hidden; text-overflow: ellipsis;">{{ $slice['label'] }}</span>
                                </div>
                                <div style="font-size: 0.9375rem; color: var(--muted-foreground);">{{ $slice['count'] }} ({{ $slice['pct'] }}%)</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); display:flex; justify-content: space-between;">
                        <span style="font-size: 0.875rem; color: var(--muted-foreground);">Total</span>
                        <strong style="font-size: 0.9375rem;">{{ $gatePassStatusTotal }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Assets by Category</h3>
            <span class="badge badge-secondary">Horizontal bars</span>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction: column; gap: 0.875rem;">
                @foreach($assetCategoryBars as $row)
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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Weekly Gate Passes</h3>
            <span class="badge badge-secondary">Bar chart</span>
        </div>
        <div class="card-body">
            <div style="display:flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <div style="font-size: 0.875rem; color: var(--muted-foreground);">Issued this week</div>
                    <div style="font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em;">{{ collect($gatePassWeeklyBars)->sum('value') }}</div>
                </div>
            </div>
            <div style="border: 1px solid var(--border); border-radius: 10px; padding: 1rem; background: var(--muted);">
                <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 0.625rem; align-items: end; height: 180px;">
                    @foreach($gatePassWeeklyBars as $bar)
                        <div style="display:flex; flex-direction: column; gap: 0.5rem; align-items: stretch;">
                            <div title="{{ $bar['label'] }}: {{ $bar['value'] }}" style="height: 150px; display:flex; align-items:flex-end; position: relative;">
                                <div style="position:absolute; top: 0; left: 0; right: 0; text-align:center; font-size: 0.75rem; color: var(--muted-foreground);">{{ $bar['value'] }}</div>
                                <div style="width: 100%; height: {{ $bar['pct'] }}%; background: var(--foreground); border-radius: 8px; border: 1px solid var(--border);"></div>
                            </div>
                            <div style="font-size: 0.8125rem; color: var(--muted-foreground); text-align:center;">{{ $bar['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Maintenance Status</h3>
            <span class="badge badge-secondary">Donut chart</span>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 140px 1fr; gap: 1.25rem; align-items: center;">
                <div style="display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 42 42" width="132" height="132" style="display:block;">
                        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="var(--border)" stroke-width="6"></circle>
                        @php($offset = 25)
                        @foreach($maintenanceStatusDonut as $slice)
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
                        @foreach($maintenanceStatusDonut as $slice)
                            <div style="display:flex; align-items:center; justify-content: space-between; gap: 1rem;">
                                <div style="display:flex; align-items:center; gap: 0.5rem; min-width: 0;">
                                    <span style="width: 10px; height: 10px; border-radius: 9999px; background: {{ $slice['color'] }}; display:inline-block;"></span>
                                    <span style="font-size: 0.9375rem; color: var(--foreground); white-space: nowrap; overflow:hidden; text-overflow: ellipsis;">{{ $slice['label'] }}</span>
                                </div>
                                <div style="font-size: 0.9375rem; color: var(--muted-foreground);">{{ $slice['count'] }} ({{ $slice['pct'] }}%)</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); display:flex; justify-content: space-between;">
                        <span style="font-size: 0.875rem; color: var(--muted-foreground);">Total</span>
                        <strong style="font-size: 0.9375rem;">{{ $maintenanceStatusTotal }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Maintenance Priority</h3>
            <span class="badge badge-secondary">Horizontal bars</span>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction: column; gap: 0.875rem;">
                @foreach($maintenancePriorityBars as $row)
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
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Weekly Repairs</h3>
            <span class="badge badge-secondary">Bar chart</span>
        </div>
        <div class="card-body">
            <div style="display:flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <div style="font-size: 0.875rem; color: var(--muted-foreground);">Completed this week</div>
                    <div style="font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em;">{{ collect($maintenanceWeeklyBars)->sum('value') }}</div>
                </div>
            </div>
            <div style="border: 1px solid var(--border); border-radius: 10px; padding: 1rem; background: var(--muted);">
                <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 0.625rem; align-items: end; height: 180px;">
                    @foreach($maintenanceWeeklyBars as $bar)
                        <div style="display:flex; flex-direction: column; gap: 0.5rem; align-items: stretch;">
                            <div title="{{ $bar['label'] }}: {{ $bar['value'] }}" style="height: 150px; display:flex; align-items:flex-end; position: relative;">
                                <div style="position:absolute; top: 0; left: 0; right: 0; text-align:center; font-size: 0.75rem; color: var(--muted-foreground);">{{ $bar['value'] }}</div>
                                <div style="width: 100%; height: {{ $bar['pct'] }}%; background: var(--foreground); border-radius: 8px; border: 1px solid var(--border);"></div>
                            </div>
                            <div style="font-size: 0.8125rem; color: var(--muted-foreground); text-align:center;">{{ $bar['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Top Repair Vendors</h3>
            <span class="badge badge-secondary">Horizontal bars</span>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction: column; gap: 0.875rem;">
                @foreach($vendorBars as $row)
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
</div>

<div class="grid-2" style="margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1.0625rem;">Priority Distribution</h3>
            <span class="badge badge-secondary">Horizontal bars</span>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction: column; gap: 0.875rem;">
                @foreach($priorityBars as $row)
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
</div>

@endsection
