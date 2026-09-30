<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Minutes Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Minutes Approved</h2>
    <p>Minutes for the following meeting have been approved:</p>
    <ul>
        <li><strong>Meeting:</strong> {{ $meeting->title }}</li>
        <li><strong>Date:</strong> {{ $meeting->meeting_date->format('M d, Y') }}</li>
        <li><strong>Approved At:</strong> {{ $meeting->approved_at->format('M d, Y H:i') }}</li>
    </ul>
    <p><a href="{{ route('meetings.show', $meeting) }}">View Minutes</a></p>
</body>
</html>
