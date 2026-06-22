<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskReminder extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $subject = $this->task->subject;

        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'due_at' => $this->task->due_at?->toIso8601String(),
            'subject_label' => $subject ? ($subject->full_name ?? $subject->title ?? null) : null,
            'url' => $this->subjectUrl($subject),
        ];
    }

    protected function subjectUrl(?object $subject): string
    {
        return match (true) {
            $subject instanceof Customer => route('customers.show', $subject),
            $subject instanceof Company => route('companies.show', $subject),
            $subject instanceof Lead => route('leads.show', $subject),
            default => route('tasks.index'),
        };
    }
}
