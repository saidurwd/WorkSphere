<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Meeting Cancelled</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Meeting Cancelled</h2>
    <p>The following meeting has been cancelled:</p>
    <ul>
        <li><strong>Title:</strong> {{ $meeting->title }}</li>
        <li><strong>Date:</strong> {{ $meeting->meeting_date->format('M d, Y') }}</li>
        <li><strong>Time:</strong> {{ $meeting->start_time->format('H:i') }} - {{ $meeting->end_time->format('H:i') }}</li>
    </ul>
</body>
</html>
