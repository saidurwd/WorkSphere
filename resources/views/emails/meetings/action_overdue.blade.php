<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Action Overdue</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2 style="color: #dc2626;">Overdue Action</h2>
    <p>The following action item is now overdue:</p>
    <ul>
        <li><strong>Action:</strong> {{ $actionItem->title }}</li>
        <li><strong>Due Date:</strong> {{ $actionItem->due_date ? $actionItem->due_date->format('M d, Y') : 'N/A' }}</li>
        <li><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $actionItem->status)) }}</li>
    </ul>
    <p><a href="{{ route('meetings.action-items.show', $actionItem) }}">View Action Item</a></p>
</body>
</html>
