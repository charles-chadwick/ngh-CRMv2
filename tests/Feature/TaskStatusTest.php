<?php

use App\Enum\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

uses(RefreshDatabase::class);

beforeEach(fn () => actingAs(User::factory()->create()));

it('completes a task', function () {
    $task = Task::factory()->create();

    patch(route('tasks.updateStatus', $task), ['status' => 'Completed'])->assertRedirect();

    expect($task->fresh()->status)->toBe(TaskStatus::Completed)
        ->and($task->fresh()->completed_at)->not->toBeNull();
});

it('logs a note on the subject when completing with log_note', function () {
    $company = Company::factory()->create();
    $task = Task::factory()->forSubject($company)->create();

    patch(route('tasks.updateStatus', $task), [
        'status' => 'Completed',
        'log_note' => 'Spoke with the client, all good.',
    ])->assertRedirect();

    expect($company->notes()->count())->toBe(1)
        ->and(strip_tags($company->notes()->first()->content))->toContain('all good');
});

it('cancels and reopens a task', function () {
    $task = Task::factory()->completed()->create();

    patch(route('tasks.updateStatus', $task), ['status' => 'Open'])->assertRedirect();
    expect($task->fresh()->status)->toBe(TaskStatus::Open)
        ->and($task->fresh()->completed_at)->toBeNull();

    patch(route('tasks.updateStatus', $task), ['status' => 'Canceled'])->assertRedirect();
    expect($task->fresh()->status)->toBe(TaskStatus::Canceled);
});

it('rejects an invalid status', function () {
    $task = Task::factory()->create();
    patch(route('tasks.updateStatus', $task), ['status' => 'Nope'])
        ->assertSessionHasErrors('status');
});
