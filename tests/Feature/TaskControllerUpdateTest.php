<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(fn () => actingAs(User::factory()->create()));

it('updates a task title and reassigns the assignee', function () {
    $task = Task::factory()->create(['title' => 'Old']);
    $assignee = User::factory()->create();

    put(route('tasks.update', $task), [
        'type' => $task->type->value,
        'title' => 'New title',
        'assignee_type' => 'user',
        'assignee_id' => $assignee->id,
    ])->assertRedirect();

    $task->refresh();
    expect($task->title)->toBe('New title')
        ->and($task->assignee->is($assignee))->toBeTrue();
});

it('respects an explicitly cleared reminder on update', function () {
    $task = Task::factory()->create(['remind_at' => now()->addDay()]);

    put(route('tasks.update', $task), [
        'type' => $task->type->value,
        'title' => $task->title,
        'due_at' => now()->addDays(2)->toDateTimeString(),
        'remind_at' => null,
    ])->assertRedirect();

    expect($task->fresh()->remind_at)->toBeNull();
});

it('soft deletes a task', function () {
    $task = Task::factory()->create();

    delete(route('tasks.destroy', $task))->assertRedirect();

    expect($task->fresh()->trashed())->toBeTrue();
});
