<?php

namespace App\Jobs;

use App\Mail\TaskReminder;
use App\Models\Task;
use App\Services\TaskNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTaskReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Task $task) {}

    public function handle(TaskNotificationService $notificationService): void
    {
        $responsibleUser = $this->task->responsibleUser;

        if ($responsibleUser && $responsibleUser->email) {
            try {
                Mail::to($responsibleUser->email)->send(new TaskReminder($this->task, $responsibleUser));

                $notificationService->log(
                    $this->task,
                    $responsibleUser,
                    'EMAIL',
                    'task_reminder',
                    'SENT',
                    'Task Reminder: '.$this->task->title,
                    'Reminder: Task "'.$this->task->title.'" is due soon.',
                );
            } catch (\Throwable $e) {
                $notificationService->log(
                    $this->task,
                    $responsibleUser,
                    'EMAIL',
                    'task_reminder',
                    'FAILED',
                    'Task Reminder: '.$this->task->title,
                    'Reminder: Task "'.$this->task->title.'" is due soon.',
                    $e->getMessage(),
                );
            }
        }
    }
}
