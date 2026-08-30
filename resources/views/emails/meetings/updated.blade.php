<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Meeting Updated</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Meeting Updated</h2>
    <p>The following meeting has been updated:</p>
    <ul>
        <li><strong>Title:</strong> {{ $meeting->title }}</li>
        <li><strong>Date:</strong> {{ $meeting->meeting_date->format('M d, Y') }}</li>
        <li><strong>Time:</strong> {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</li>
        <li><strong>Location:</strong> {{ $meeting->location ?? 'N/A' }}</li>
    </ul>
    <p><a href="{{ route('meetings.show', $meeting) }}">View Meeting Details</a></p>
</body>
</html>
