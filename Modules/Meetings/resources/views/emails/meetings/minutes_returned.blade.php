<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Minutes Returned</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Minutes Returned for Revision</h2>
    <p>Minutes for the following meeting have been returned:</p>
    <ul>
        <li><strong>Meeting:</strong> {{ $meeting->title }}</li>
        <li><strong>Date:</strong> {{ $meeting->meeting_date->format('M d, Y') }}</li>
    </ul>
    @if($comments)
    <p><strong>Reviewer Comments:</strong></p>
    <p>{{ $comments }}</p>
    @endif
    <p><a href="{{ route('meetings.show', $meeting) }}">Edit Minutes</a></p>
</body>
</html>
