<?php

use App\Enum\TaskStatus;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('casts attributes and resolves polymorphic relations', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->create();

    $task = Task::factory()
        ->forSubject($customer)
        ->assignedTo($user)
        ->allDay()
        ->create();

    expect($task->all_day)->toBeTrue()
        ->and($task->status)->toBe(TaskStatus::Open)
        ->and($task->subject->is($customer))->toBeTrue()
        ->and($task->assignee->is($user))->toBeTrue();
});

it('scopes overdue to open, past-due tasks only', function () {
    Task::factory()->overdue()->create();
    Task::factory()->completed()->overdue()->create();
    Task::factory()->create(['due_at' => now()->addWeek()]);

    expect(Task::overdue()->count())->toBe(1);
});

it('marks a task completed and reopens it', function () {
    $task = Task::factory()->create();

    $task->markCompleted();
    expect($task->fresh()->status)->toBe(TaskStatus::Completed)
        ->and($task->fresh()->completed_at)->not->toBeNull();

    $task->reopen();
    expect($task->fresh()->status)->toBe(TaskStatus::Open)
        ->and($task->fresh()->completed_at)->toBeNull();
});

it('filters by assignee', function () {
    $user = User::factory()->create();
    Task::factory()->assignedTo($user)->create();
    Task::factory()->assignedTo(User::factory()->create())->create();

    assertDatabaseHas('tasks', ['assignee_id' => $user->id]);
    expect(Task::forAssignee($user)->count())->toBe(1);
});
