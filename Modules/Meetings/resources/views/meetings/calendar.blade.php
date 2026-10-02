@extends('layouts.app')

@section('title', 'Meeting Calendar')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meeting Calendar'],
    ];
@endphp

@push('styles')
    @vite(['resources/js/fullcalendar.js'])
@endpush

@section('content')
    <x-page-header title="Meeting Calendar" subtitle="View meetings and action items on the calendar." />

    <div class="card">
        <div class="card-body">
            <div id="calendar" style="height: 700px;"></div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/fullcalendar.js'])
    <script>
        window.FullCalendar.plugins = {
            dayGrid: window.FullCalendar.dayGridPlugin,
            timeGrid: window.FullCalendar.timeGridPlugin,
            list: window.FullCalendar.listPlugin
        };

        (function () {
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
                    },
                    credentials: 'include',
                })
                    .then(function (response) {
                        console.log('Events response status:', response.status);
                        if (!response.ok) {
                            throw new Error('HTTP error ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        console.log('Events data received:', data);
                        if (!Array.isArray(data) || data.length === 0) {
                            calendarEl.innerHTML = '<p style="padding: 20px; color: var(--muted-foreground);">No meetings or action items found.</p>';
                            return;
                        }

                        try {
                            var calendar = new FullCalendar.Calendar(calendarEl, {
                                plugins: Object.values(window.FullCalendar.plugins),
                                initialView: 'dayGridMonth',
                                headerToolbar: {
                                    left: 'prev,next today',
                                    center: 'title',
                                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                                },
                                events: data,
                                eventClick: function (info) {
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
                    .catch(function (error) {
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
