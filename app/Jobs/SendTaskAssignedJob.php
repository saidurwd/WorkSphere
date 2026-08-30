<?php

namespace App\Jobs;

use App\Mail\TaskAssigned;
use App\Models\Task;
use App\Services\TaskNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskAssignedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function handle(TaskNotificationService $notificationService): void
    {
        $assignee = $this->task->responsibleUser;
        $assigner = $this->task->user;
        $remarks = $this->task->remarks()->with('user')->latest()->get();

        if ($assignee && $assignee->email && $assigner && $assignee->id !== $assigner->id) {
            try {
                Mail::to($assignee->email)->send(new TaskAssigned($this->task, $assignee, $assigner, $remarks));

                $notificationService->log(
                    $this->task,
                    null,
                    $assignee,
                    'EMAIL',
                    'task_assigned',
                    'SENT',
                    'Task Assigned: '.$this->task->title,
                    'You have been assigned to task: '.$this->task->title,
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->task,
                    null,
                    $assignee,
                    'EMAIL',
                    'task_assigned',
                    'FAILED',
                    'Task Assigned: '.$this->task->title,
                    'You have been assigned to task: '.$this->task->title,
                    $e->getMessage(),
                );
            }
        }
    }
}
