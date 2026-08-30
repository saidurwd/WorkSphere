<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Details - {{ $meeting->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #1a1a1a;
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24pt;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .header .meta {
            font-size: 11pt;
            color: #666;
            margin-top: 5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 9pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 11pt;
            font-weight: 600;
            color: #1a1a1a;
        }

        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 14pt;
            font-weight: 700;
            color: #1a1a1a;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-content {
            padding: 0;
        }

        .agenda-item, .decision-item, .action-item, .participant-item, .attachment-item {
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .agenda-item:last-child, .decision-item:last-child, .action-item:last-child, .participant-item:last-child, .attachment-item:last-child {
            border-bottom: none;
        }

        .item-header {
            font-weight: 600;
            font-size: 11pt;
            margin-bottom: 4px;
            color: #1a1a1a;
        }

        .item-sub {
            font-size: 10pt;
            color: #666;
            margin-top: 4px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-right: 6px;
        }

        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .description {
            margin-top: 8px;
            padding: 10px;
            background: #f8f9fa;
            border-left: 3px solid #1a1a1a;
            font-size: 10pt;
            color: #444;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #f8f9fa;
            padding: 8px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 10pt;
            border-bottom: 2px solid #1a1a1a;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 10pt;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 9pt;
            color: #999;
        }

        @media print {
            body {
                padding: 20px;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $meeting->title }}</h1>
        <div class="meta">
            {{ $meeting->meeting_no }} &middot; {{ $meeting->meeting_date->format('M d, Y') }} &middot; {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}
        </div>
        <div class="meta">
            Generated on {{ now()->format('M d, Y H:i') }}
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value">{{ ucwords(str_replace('_', ' ', $meeting->status)) }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Type</div>
            <div class="info-value">{{ $meeting->type->name ?? 'N/A' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Organizer</div>
            <div class="info-value">{{ $meeting->organizer->name ?? 'N/A' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Chairperson</div>
            <div class="info-value">{{ $meeting->chairperson->name ?? 'N/A' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Department</div>
            <div class="info-value">{{ $meeting->department->department_name ?? 'N/A' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Minutes Status</div>
            <div class="info-value">{{ ucwords(str_replace('_', ' ', $meeting->minutes_status)) }}</div>
        </div>
    </div>

    @if($meeting->description)
    <div class="section">
        <div class="section-title">Description</div>
        <div class="section-content">
            <div class="description">{{ $meeting->description }}</div>
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Agenda</div>
        <div class="section-content">
            @if($meeting->agendas->isNotEmpty())
                @foreach($meeting->agendas as $agenda)
                <div class="agenda-item">
                    <div class="item-header">{{ $agenda->agenda_no }}. {{ $agenda->title }}</div>
                    @if($agenda->description)
                    <div class="item-sub">{{ $agenda->description }}</div>
                    @endif
                    <div class="item-sub">
                        Presenter: {{ $agenda->presentedBy->name ?? 'N/A' }} &middot;
                        Est. Minutes: {{ $agenda->estimated_minutes ?? 'N/A' }} &middot;
                        Status: {{ ucwords(str_replace('_', ' ', $agenda->status)) }}
                    </div>
                </div>
                @endforeach
            @else
                <div class="empty-state">No agenda items.</div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Decisions</div>
        <div class="section-content">
            @if($meeting->decisions->isNotEmpty())
                @foreach($meeting->decisions as $decision)
                <div class="decision-item">
                    <div class="item-header">{{ $decision->decision_no }}. {{ $decision->decision_title }}</div>
                    @if($decision->decision_description)
                    <div class="item-sub">{{ $decision->decision_description }}</div>
                    @endif
                    <div class="item-sub">
                        <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $decision->decision_type)) }}</span>
                        <span class="badge badge-secondary">{{ ucwords($decision->decision_status) }}</span>
                        @if($decision->approvedBy)
                        Approved By: {{ $decision->approvedBy->name }}
                        @endif
                    </div>
                    @if($decision->remarks)
                    <div class="item-sub" style="font-style: italic; margin-top: 4px;">{{ $decision->remarks }}</div>
                    @endif
                </div>
                @endforeach
            @else
                <div class="empty-state">No decisions recorded.</div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Action Items</div>
        <div class="section-content">
            @if($meeting->actionItems->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Assigned To</th>
                            <th>Department</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->actionItems as $item)
                        <tr>
                            <td>{{ $item->action_no }}</td>
                            <td>{{ $item->title }}</td>
                            <td>{{ $item->assignedTo->name ?? 'N/A' }}</td>
                            <td>{{ $item->assignedDepartment->department_name ?? 'N/A' }}</td>
                            <td>{{ $item->due_date ? $item->due_date->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ ucwords($item->priority) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $item->status)) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">No action items.</div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Participants</div>
        <div class="section-content">
            @if($meeting->participants->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Attendance</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->participants as $participant)
                        <tr>
                            <td>{{ $participant->user->name ?? 'N/A' }}</td>
                            <td>{{ ucwords($participant->participant_type) }}</td>
                            <td>{{ ucwords($participant->attendance_status) }}</td>
                            <td>{{ $participant->remarks ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">No participants.</div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Attachments</div>
        <div class="section-content">
            @if($meeting->attachments->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meeting->attachments as $attachment)
                        <tr>
                            <td>{{ $attachment->file_name }}</td>
                            <td>{{ $attachment->file_type ?? 'N/A' }}</td>
                            <td>{{ $attachment->file_size ? round($attachment->file_size / 1024, 1) . ' KB' : 'N/A' }}</td>
                            <td>{{ $attachment->description ?: 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">No attachments.</div>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>This document was generated from the Meeting Management System.</p>
        <p>Printed on {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
