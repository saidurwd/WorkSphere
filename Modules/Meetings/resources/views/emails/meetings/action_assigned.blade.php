<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Action Assigned</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Action Assigned to You</h2>
    <p>You have been assigned the following action item:</p>
    <ul>
        <li><strong>Action:</strong> {{ $actionItem->title }}</li>
        <li><strong>Meeting:</strong> {{ $actionItem->meeting->title ?? 'N/A' }}</li>
        <li><strong>Due Date:</strong> {{ $actionItem->due_date ? $actionItem->due_date->format('M d, Y') : 'N/A' }}</li>
        <li><strong>Priority:</strong> {{ ucfirst($actionItem->priority) }}</li>
    </ul>
    <p><a href="{{ route('meetings.action-items.show', $actionItem) }}">View Action Item</a></p>
</body>
</html>
