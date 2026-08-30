<?php

namespace App\Jobs;

use App\Mail\TaskUpdated;
use App\Models\Task;
use App\Services\TaskNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskUpdatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function handle(TaskNotificationService $notificationService): void
    {
        $responsibleUser = $this->task->responsibleUser;

        if ($responsibleUser && $responsibleUser->email) {
            try {
                Mail::to($responsibleUser->email)->send(new TaskUpdated($this->task, $responsibleUser));

                $notificationService->log(
                    $this->task,
                    null,
                    $responsibleUser,
                    'EMAIL',
                    'task_updated',
                    'SENT',
                    'Task Updated: '.$this->task->title,
                    'Task has been updated: '.$this->task->title,
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->task,
                    null,
                    $responsibleUser,
                    'EMAIL',
                    'task_updated',
                    'FAILED',
                    'Task Updated: '.$this->task->title,
                    'Task has been updated: '.$this->task->title,
                    $e->getMessage(),
                );
            }
        }
    }
}
