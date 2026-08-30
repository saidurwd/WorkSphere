@extends('tyro-dashboard::layouts.admin')

@section('title', 'Obligations Calendar')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.dashboard') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>Calendar</span>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Obligations Calendar</h1>
            <p class="page-description">View expiry dates, task due dates, and renewal schedules.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="obligation-calendar" style="min-height: 600px;"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
(function() {
    function initCalendar() {
        var calendarEl = document.getElementById('obligation-calendar');
        if (!calendarEl) {
            console.error('Calendar element not found');
            return;
        }

        var eventsUrl = @json(route('obligations.calendar.events'));
        console.log('Calendar events URL:', eventsUrl);

        fetch(eventsUrl, {
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(function(response) {
            console.log('Events response status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Events data:', data);
            if (!Array.isArray(data) || data.length === 0) {
                calendarEl.innerHTML = '<p style="padding: 20px; color: var(--muted-foreground);">No upcoming obligations or tasks found for the current view.</p>';
                return;
            }

            try {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: data,
                    eventClick: function(info) {
                        if (info.event.extendedProps.type === 'obligation') {
                            window.location.href = info.event.url;
                        } else if (info.event.extendedProps.type === 'task') {
                            window.location.href = info.event.url;
                        }
                    }
                });
                calendar.render();
                console.log('Calendar rendered successfully with', data.length, 'events');
            } catch (e) {
                console.error('Calendar initialization error:', e);
                calendarEl.innerHTML = '<p style="color: red; padding: 20px;">Failed to initialize calendar: ' + e.message + '</p>';
            }
        })
        .catch(function(error) {
            console.error('Failed to fetch events:', error);
            calendarEl.innerHTML = '<p style="color: red; padding: 20px;">Failed to load calendar events: ' + error.message + '</p>';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendar);
    } else {
        initCalendar();
    }
})();
</script>
@endpush
@endsection
