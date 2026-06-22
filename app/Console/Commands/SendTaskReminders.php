<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-task-reminders')]
#[Description('Send in-app reminders for due, open, assigned tasks')]
class SendTaskReminders extends Command
{
    public function handle(): int
    {
        Task::query()
            ->open()
            ->whereNotNull('remind_at')
            ->where('remind_at', '<=', now())
            ->whereNull('reminded_at')
            ->whereNotNull('assignee_id')
            ->with('assignee')
            ->chunkById(100, function ($tasks): void {
                foreach ($tasks as $task) {
                    $task->assignee?->notify(new TaskReminder($task));
                    $task->forceFill(['reminded_at' => now()])->save();
                }
            });

        return self::SUCCESS;
    }
}
