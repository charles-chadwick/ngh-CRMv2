<?php

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(fn () => actingAs(User::factory()->create()));

it('passes paginated tasks and option lists to the company show page', function () {
    $company = Company::factory()->create();
    Task::factory()->forSubject($company)->count(2)->create();

    get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 2)
            ->has('task_types')
            ->has('task_statuses')
        );
});
