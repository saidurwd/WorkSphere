<?php

namespace App\Jobs;

use App\Mail\TaskCompleted;
use App\Models\Task;
use App\Services\TaskNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskCompletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function handle(TaskNotificationService $notificationService): void
    {
        $creator = $this->task->user;
        $responsibleUser = $this->task->responsibleUser;

        if ($creator && $creator->email && $creator->id !== $responsibleUser?->id) {
            try {
                Mail::to($creator->email)->send(new TaskCompleted($this->task, $creator));

                $notificationService->log(
                    $this->task,
                    null,
                    $creator,
                    'EMAIL',
                    'task_completed',
                    'SENT',
                    'Task Completed: '.$this->task->title,
                    'Task has been marked as completed: '.$this->task->title,
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->task,
                    null,
                    $creator,
                    'EMAIL',
                    'task_completed',
                    'FAILED',
                    'Task Completed: '.$this->task->title,
                    'Task has been marked as completed: '.$this->task->title,
                    $e->getMessage(),
                );
            }
        }
    }
}
