<x-mail::message>
# Task Assigned

Hello {{ $assignee->name }},

A new task has been assigned to you by **{{ $assigner->name }}**.

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

@if($remarks->isNotEmpty())
## Task Remarks

@foreach($remarks as $remark)
**{{ $remark->user->name }}** — {{ $remark->created_at->format('F j, Y g:i A') }}

{{ $remark->remark }}

@if($remark->attachment)
**Attachment:** {{ basename($remark->attachment) }}
@endif

---
@endforeach
@endif

Please review the task and update its status as you make progress.

<x-mail::button :url="route('tasks.show', $task)">
View Task
</x-mail::button>

Thank you for your attention to this matter.

{{ config('app.name') }}
</x-mail::message>
