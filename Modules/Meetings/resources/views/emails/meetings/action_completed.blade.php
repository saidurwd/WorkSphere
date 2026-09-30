<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Action Completed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Action Completed</h2>
    <p>The following action item has been completed:</p>
    <ul>
        <li><strong>Action:</strong> {{ $actionItem->title }}</li>
        <li><strong>Meeting:</strong> {{ $actionItem->meeting->title ?? 'N/A' }}</li>
        <li><strong>Assigned To:</strong> {{ $actionItem->assignedTo->name ?? 'N/A' }}</li>
        <li><strong>Completed At:</strong> {{ $actionItem->completed_at ? $actionItem->completed_at->format('M d, Y H:i') : 'N/A' }}</li>
    </ul>
    <p><a href="{{ route('meetings.action-items.show', $actionItem) }}">View Action Item</a></p>
</body>
</html>
