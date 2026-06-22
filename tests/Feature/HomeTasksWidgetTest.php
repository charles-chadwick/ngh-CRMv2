<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows the current user\'s overdue tasks on the dashboard', function () {
    $user = User::factory()->create();
    actingAs($user);

    Task::factory()->assignedTo($user)->overdue()->create();
    Task::factory()->assignedTo($user)->create(['due_at' => now()->addWeek()]); // not overdue
    Task::factory()->assignedTo(User::factory()->create())->overdue()->create(); // someone else's

    get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('my_tasks', 1)
        );
});
