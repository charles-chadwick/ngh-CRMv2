<?php

use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes a polymorphic tasks relation ordered by due date', function () {
    $company = Company::factory()->create();
    $later = Task::factory()->forSubject($company)->create(['due_at' => now()->addWeek()]);
    $sooner = Task::factory()->forSubject($company)->create(['due_at' => now()->addDay()]);

    expect($company->tasks()->pluck('id')->all())->toBe([$sooner->id, $later->id]);
});

it('paginates a record\'s tasks under the tasks_page name', function () {
    $lead = Lead::factory()->create();
    Task::factory()->count(7)->forSubject($lead)->create();

    $paginated = $lead->paginatedTasks();

    expect($paginated->perPage())->toBe(5)
        ->and($paginated->total())->toBe(7)
        ->and($paginated->getPageName())->toBe('tasks_page');
});
