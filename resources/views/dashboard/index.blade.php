@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-body-secondary mb-0">{{ now()->format('l, F j, Y') }}</p>

        <div class="btn-group btn-group-sm" role="group" aria-label="Module dashboards">
            <x-btn :href="route('tasks.dashboard')" variant="outline-secondary" size="sm" icon="check2-square">Tasks</x-btn>
            <x-btn :href="route('meetings.dashboard')" variant="outline-secondary" size="sm" icon="journal-text">Meetings</x-btn>
            <x-btn :href="route('obligations.dashboard')" variant="outline-secondary" size="sm" icon="file-earmark-text">Obligations</x-btn>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Total Tasks" :value="$taskTotal" icon="list-task" variant="primary" :href="route('tasks.index')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Completed Tasks" :value="$taskCompleted" icon="check2-all" variant="success" :href="route('tasks.index', ['status' => 'completed'])" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Pending Tasks" :value="$taskPending" icon="hourglass-split" variant="warning" :href="route('tasks.index', ['status' => 'pending'])" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Overdue Tasks" :value="$taskOverdue" icon="exclamation-triangle" variant="danger" :href="route('tasks.index')" />
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Meetings This Month" :value="$meetingThisMonth" icon="calendar-week" variant="info" :href="route('meetings.index')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Upcoming Meetings" :value="$meetingUpcoming" icon="calendar-event" variant="primary" :href="route('meetings.calendar')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Pending Actions" :value="$pendingActions" icon="list-check" variant="warning" :href="route('meetings.action-items.index')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Overdue Actions" :value="$overdueActions" icon="exclamation-octagon" variant="danger" :href="route('meetings.reports.overdue')" />
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Active Obligations" :value="$obligationActive" icon="journal-check" variant="primary" :href="route('obligations.index')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Due Within 7 Days" :value="$obligationDue7" icon="calendar-week" variant="warning" :href="route('obligations.renewals')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Expired" :value="$obligationExpired" icon="x-octagon" variant="danger" :href="route('obligations.index')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-small-box title="Critical Risk" :value="$obligationCritical" icon="shield-exclamation" variant="danger" :href="route('obligations.reports')" />
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Task Status</h3>
                </div>

                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap">
                        <x-donut-chart :slices="$statusDonut" size="160" />
                        <x-legend :slices="$statusDonut" :total="$statusTotal" class="flex-grow-1" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Weekly Tasks</h3>
                    <span class="badge text-bg-secondary">Created this week</span>
                </div>

                <div class="card-body">
                    <x-bar-chart :bars="$weeklyBars" height="180" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Priority Distribution</h3>
                </div>

                <div class="card-body">
                    <x-progress-list :rows="$priorityBars" />
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <x-detail-card
                title="Upcoming Tasks"
                icon="calendar-week"
                :items="$upcomingTasks"
                empty-message="No upcoming tasks."
                :view-all-route="route('tasks.index')"
                view-all-label="View all"
                class="h-100"
            />
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Meeting Management</h3>
                    <a href="{{ route('meetings.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        Meeting Dashboard
                    </a>
                </div>

                <div class="card-body">
                    @if ($upcomingMeetings->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach ($upcomingMeetings as $meeting)
                                <a href="{{ route('meetings.show', $meeting) }}" class="list-group-item list-group-item-action">
                                    <div class="fw-semibold">{{ $meeting->title }}</div>
                                    <div class="small text-body-secondary">
                                        {{ $meeting->meeting_date->format('M d, Y') }} &middot; {{ $meeting->start_time->format('H:i') }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-body-secondary text-center mb-0 py-4">No upcoming meetings.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">My Pending Actions</h3>
                    <a href="{{ route('meetings.action-items.index') }}" class="btn btn-sm btn-outline-secondary">
                        View all
                    </a>
                </div>

                <div class="card-body">
                    @if ($myPendingActions->isNotEmpty())
                        <div class="list-group list-group-flush">
                            @foreach ($myPendingActions as $action)
                                <div class="list-group-item px-0">
                                    <div class="fw-semibold">{{ $action->title }}</div>
                                    <div class="small text-body-secondary">
                                        Due {{ $action->due_date->format('M d, Y') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-body-secondary text-center mb-0 py-4">No pending actions.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Compliance &amp; Obligations</h3>
                    <a href="{{ route('obligations.index') }}" class="btn btn-sm btn-outline-secondary">
                        View all
                    </a>
                </div>

                <div class="card-body">
                    <x-progress-list :rows="$typeBars" />
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Priority Distribution</h3>
                </div>

                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap">
                        <x-donut-chart :slices="$priorityDonut" size="160" />
                        <x-legend :slices="$priorityDonut" :total="$priorityTotal" class="flex-grow-1" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <x-detail-card
                title="Upcoming Obligations"
                icon="calendar-week"
                :items="$upcomingObligations"
                empty-message="No upcoming obligations."
                :view-all-route="route('obligations.index')"
                view-all-label="View all"
                class="h-100"
            />
        </div>

        <div class="col-lg-6">
            <x-detail-card
                title="Critical Obligations"
                icon="shield-exclamation"
                variant="danger"
                :items="$criticalObligations"
                empty-message="No critical obligations."
                :view-all-route="route('obligations.reports')"
                view-all-label="View report"
                class="h-100"
            />
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <x-detail-card
                title="Expired Obligations"
                icon="x-octagon"
                variant="danger"
                :items="$expiredObligations"
                empty-message="No expired obligations."
                :view-all-route="route('obligations.index')"
                view-all-label="View all"
            />
        </div>
    </div>
@endsection
