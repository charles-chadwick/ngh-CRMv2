<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the tasks index with grouped lists for the current user', function () {
    $user = User::factory()->create();
    actingAs($user);

    Task::factory()->assignedTo($user)->overdue()->create();
    Task::factory()->assignedTo($user)->dueToday()->create();
    Task::factory()->assignedTo($user)->create(['due_at' => now()->addWeek()]);
    Task::factory()->assignedTo(User::factory()->create())->overdue()->create(); // someone else's

    get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('overdue', 1)
            ->has('due_today', 1)
            ->has('upcoming', 1)
        );
});

it('returns a calendar feed for the requested range', function () {
    $user = User::factory()->create();
    actingAs($user);
    Task::factory()->assignedTo($user)->create(['due_at' => now()->startOfMonth()->addDays(2)]);

    get(route('tasks.index', [
        'start' => now()->startOfMonth()->toDateString(),
        'end' => now()->endOfMonth()->toDateString(),
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('calendar', 1));
});
