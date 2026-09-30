<?php

namespace Modules\Tasks\Jobs;

use Modules\Tasks\Mail\TaskOverdue;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskOverdueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function handle(TaskNotificationService $notificationService): void
    {
        $responsibleUser = $this->task->responsibleUser;

        if ($responsibleUser && $responsibleUser->email) {
            try {
                Mail::to($responsibleUser->email)->send(new TaskOverdue($this->task, $responsibleUser));

                $notificationService->log(
                    $this->task,
                    $responsibleUser,
                    'EMAIL',
                    'task_overdue',
                    'SENT',
                    'Task Overdue: '.$this->task->title,
                    'Overdue: Task "'.$this->task->title.'" is past its due date.',
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->task,
                    $responsibleUser,
                    'EMAIL',
                    'task_overdue',
                    'FAILED',
                    'Task Overdue: '.$this->task->title,
                    'Overdue: Task "'.$this->task->title.'" is past its due date.',
                    $e->getMessage(),
                );
            }
        }
    }
}
