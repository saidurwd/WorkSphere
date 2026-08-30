<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Minutes Submitted</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Minutes Submitted for Approval</h2>
    <p>Minutes for the following meeting have been submitted for your approval:</p>
    <ul>
        <li><strong>Meeting:</strong> {{ $meeting->title }}</li>
        <li><strong>Date:</strong> {{ $meeting->meeting_date->format('M d, Y') }}</li>
        <li><strong>Minutes Status:</strong> {{ ucwords(str_replace('_', ' ', $meeting->minutes_status)) }}</li>
    </ul>
    <p><a href="{{ route('meetings.show', $meeting) }}">Review Minutes</a></p>
</body>
</html>
