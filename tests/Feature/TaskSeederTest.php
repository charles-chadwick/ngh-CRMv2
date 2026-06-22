<?php

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds tasks against existing records without creating users', function () {
    User::factory()->count(2)->create();
    Company::factory()->count(2)->create();

    $users_before = User::count();
    (new TaskSeeder)->run();

    expect(Task::count())->toBeGreaterThan(0)
        ->and(User::count())->toBe($users_before);
});

it('does nothing when there are no records to attach to', function () {
    (new TaskSeeder)->run();
    expect(Task::count())->toBe(0);
});
