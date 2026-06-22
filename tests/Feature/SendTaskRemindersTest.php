<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('notifies the assignee once for a due open task', function () {
    Notification::fake();
    $user = User::factory()->create();
    $task = Task::factory()->assignedTo($user)->create([
        'remind_at' => now()->subMinute(),
        'reminded_at' => null,
    ]);

    $this->artisan('app:send-task-reminders')->assertSuccessful();

    Notification::assertSentTo($user, TaskReminder::class);
    expect($task->fresh()->reminded_at)->not->toBeNull();
});

it('does not re-notify an already reminded task', function () {
    Notification::fake();
    $user = User::factory()->create();
    Task::factory()->assignedTo($user)->create([
        'remind_at' => now()->subHour(),
        'reminded_at' => now()->subMinute(),
    ]);

    $this->artisan('app:send-task-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('skips completed, future, and unassigned tasks', function () {
    Notification::fake();
    Task::factory()->completed()->create(['remind_at' => now()->subMinute()]);
    Task::factory()->create(['remind_at' => now()->addHour()]);
    Task::factory()->create(['remind_at' => now()->subMinute(), 'assignee_id' => null]);

    $this->artisan('app:send-task-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});
