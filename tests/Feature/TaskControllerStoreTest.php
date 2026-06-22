<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(fn () => actingAs(User::factory()->create()));

it('creates a task against a company assigned to a user', function () {
    $company = Company::factory()->create();
    $assignee = User::factory()->create();

    post(route('tasks.store'), [
        'subject_type' => 'company',
        'subject_id' => $company->id,
        'assignee_type' => 'user',
        'assignee_id' => $assignee->id,
        'type' => 'Call',
        'title' => 'Quarterly review call',
        'due_at' => now()->addDay()->toDateTimeString(),
        'all_day' => false,
    ])->assertRedirect();

    $task = Task::first();
    expect($task->subject->is($company))->toBeTrue()
        ->and($task->assignee->is($assignee))->toBeTrue()
        ->and($task->title)->toBe('Quarterly review call');
});

it('creates a standalone task with no subject', function () {
    post(route('tasks.store'), [
        'type' => 'To-Do',
        'title' => 'Personal reminder',
        'due_at' => now()->addDay()->toDateTimeString(),
    ])->assertRedirect();

    assertDatabaseHas('tasks', ['title' => 'Personal reminder', 'subject_id' => null]);
});

it('defaults remind_at to due_at when omitted', function () {
    $due = now()->addDays(3)->startOfMinute();

    post(route('tasks.store'), [
        'type' => 'To-Do',
        'title' => 'Reminder defaulting',
        'due_at' => $due->toDateTimeString(),
    ])->assertRedirect();

    expect(Task::first()->remind_at->toDateTimeString())->toBe($due->toDateTimeString());
});

it('assigns a customer as assignee', function () {
    $customer = Customer::factory()->create();

    post(route('tasks.store'), [
        'type' => 'Follow Up',
        'title' => 'Waiting on docs',
        'assignee_type' => 'customer',
        'assignee_id' => $customer->id,
    ])->assertRedirect();

    expect(Task::first()->assignee->is($customer))->toBeTrue();
});

it('rejects an invalid type', function () {
    post(route('tasks.store'), ['type' => 'Bogus', 'title' => 'x'])
        ->assertSessionHasErrors('type');
});
