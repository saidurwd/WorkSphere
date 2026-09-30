<x-mail::message>
# Task Overdue

Hello {{ $user->name }},

The following task is now overdue:

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
</x-mail::table>

Please review the task and update its status as soon as possible.

<x-mail::button :url="route('tasks.show', $task)">
View Task
</x-mail::button>

Thank you for your attention to this matter.

{{ config('app.name') }}
</x-mail::message>
