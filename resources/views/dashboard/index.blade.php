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
            <p class="page-description">Overview of your tasks, meetings, and compliance obligations.</p>
        </div>
        <div>
            <span style="font-size: 0.875rem; color: var(--muted-foreground);">{{ now()->format('l, F j, Y') }}</span>
        </div>
    </div>
</div>

<div class="stats-grid">
    <x-stat label="Total Tasks" value="{{ $taskTotal }}" variant="primary" />
    <x-stat label="Completed Tasks" value="{{ $taskCompleted }}" variant="success" />
    <x-stat label="Pending Tasks" value="{{ $taskPending }}" variant="warning" />
    <x-stat label="Task Overdue" value="{{ $taskOverdue }}" variant="destructive" />
    <x-stat label="Meetings This Month" value="{{ $meetingThisMonth }}" variant="primary" />
    <x-stat label="Upcoming Meetings" value="{{ $meetingUpcoming }}" variant="info" />
    <x-stat label="Pending Actions" value="{{ $pendingActions }}" variant="warning" />
    <x-stat label="Overdue Actions" value="{{ $overdueActions }}" variant="destructive" />
    <x-stat label="Active Obligations" value="{{ $obligationActive }}" variant="primary" />
    <x-stat label="Due Within 7 Days" value="{{ $obligationDue7 }}" variant="warning" />
    <x-stat label="Expired" value="{{ $obligationExpired }}" variant="destructive" />
    <x-stat label="Critical Risk" value="{{ $obligationCritical }}" variant="destructive" />
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Task Management</h2>
        <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-secondary">View All Tasks</a>
    </div>
    <div class="card-body">
        <div class="grid-2" style="margin-bottom: 1.5rem;">
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Task Status</h3>
                </div>
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
                                        <span style="font-size: 0.9375rem; color: var(--foreground);">{{ $slice['label'] }}</span>
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
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Weekly Tasks</h3>
                </div>
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

        <div class="grid-2">
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Priority Distribution</h3>
                </div>
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
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Upcoming Tasks</h3>
                </div>
                @if(count($upcomingTasks))
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingTasks as $task)
                                <tr>
                                    <td><a href="{{ $task['url'] }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $task['title'] }}</a></td>
                                    <td>{{ $task['subtitle'] }}</td>
                                    <td><span class="badge {{ $task['badge']['class'] }}">{{ $task['badge']['text'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--muted-foreground);">No upcoming tasks.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Meeting Management</h2>
        <a href="{{ route('meetings.dashboard') }}" class="btn btn-sm btn-secondary">Meeting Dashboard</a>
    </div>
    <div class="card-body">
        <div class="grid-2">
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Upcoming Meetings</h3>
                </div>
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
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">My Pending Actions</h3>
                </div>
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
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Compliance & Obligations</h2>
        <a href="{{ route('obligations.index') }}" class="btn btn-sm btn-secondary">View All Obligations</a>
    </div>
    <div class="card-body">
        <div class="grid-2" style="margin-bottom: 1.5rem;">
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Obligations by Type</h3>
                </div>
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
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Priority Distribution</h3>
                </div>
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

        <div class="grid-2" style="margin-bottom: 1.5rem;">
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Upcoming Obligations</h3>
                </div>
                @if(count($upcomingObligations))
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Remaining</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingObligations as $item)
                                <tr>
                                    <td><a href="{{ $item['url'] }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $item['title'] }}</a></td>
                                    <td>{{ $item['subtitle'] }}</td>
                                    <td><span class="badge {{ $item['badge']['class'] }}">{{ $item['badge']['text'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--muted-foreground);">No upcoming obligations.</p>
                @endif
            </div>
            <div>
                <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                    <h3 class="card-title" style="font-size: 1.0625rem;">Critical Obligations</h3>
                </div>
                @if(count($criticalObligations))
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Remaining</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criticalObligations as $item)
                                <tr>
                                    <td><a href="{{ $item['url'] }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $item['title'] }}</a></td>
                                    <td>{{ $item['subtitle'] }}</td>
                                    <td><span class="badge {{ $item['badge']['class'] }}">{{ $item['badge']['text'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--muted-foreground);">No critical obligations.</p>
                @endif
            </div>
        </div>

        <div>
            <div class="card-header" style="padding: 0 0 1rem 0; border: none;">
                <h3 class="card-title" style="font-size: 1.0625rem;">Expired Obligations</h3>
            </div>
            @if(count($expiredObligations))
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Remaining</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiredObligations as $item)
                            <tr>
                                <td><a href="{{ $item['url'] }}" style="text-decoration: none; color: inherit; font-weight: 500;">{{ $item['title'] }}</a></td>
                                <td>{{ $item['subtitle'] }}</td>
                                <td><span class="badge {{ $item['badge']['class'] }}">{{ $item['badge']['text'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: var(--muted-foreground);">No expired obligations.</p>
            @endif
        </div>
    </div>
</div>
@endsection
