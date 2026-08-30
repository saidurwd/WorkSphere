<x-mail::message>
# Task Completed

Hello {{ $user->name }},

The following task has been marked as completed:

<x-mail::panel>
**{{ $task->title }}**

{{ $task->description ?: 'No description was provided for this task.' }}
</x-mail::panel>

<x-mail::table>
| Detail | Information |
| :----- | :---------- |
| **Priority** | {{ ucfirst($task->priority) }} |
| **Status** | {{ ucfirst(str_replace('_', ' ', $task->status)) }} |
| **Due Date** | {{ $task->due_date->format('F j, Y') }} |
| **Completed At** | {{ $task->completed_at ? $task->completed_at->format('F j, Y g:i A') : 'N/A' }} |
</x-mail::table>

<x-mail::button :url="route('tasks.show', $task)">
View Task
</x-mail::button>

Thank you for your attention to this matter.

{{ config('app.name') }}
</x-mail::message>
