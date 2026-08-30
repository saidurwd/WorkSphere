@extends('tyro-dashboard::layouts.admin')

@section('title', 'Meeting Calendar')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Meeting Calendar</span>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Meeting Calendar</h1>
            <p class="page-description">View meetings and action items on the calendar.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendar" style="height: 700px;"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
(function() {
    function initCalendar() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) {
            console.error('Calendar element not found');
            return;
        }

        var eventsUrl = '{{ route('meetings.calendar.events') }}';
        console.log('Loading calendar events from:', eventsUrl);

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
            console.log('Events data received:', data);
            if (!Array.isArray(data) || data.length === 0) {
                calendarEl.innerHTML = '<p style="padding: 20px; color: var(--muted-foreground);">No meetings or action items found.</p>';
                return;
            }

            try {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                    },
                    events: data,
                    eventClick: function(info) {
                        if (info.event.url) {
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
