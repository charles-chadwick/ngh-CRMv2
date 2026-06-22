<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(fn () => actingAs(User::factory()->create()));

it('searches users and customers as assignees', function () {
    User::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Tester']);
    Customer::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Client']);

    $results = getJson(route('tasks.assignees', ['q' => 'Zelda']))->assertOk()->json();

    expect(collect($results)->pluck('type')->unique()->sort()->values()->all())
        ->toBe(['customer', 'user']);
});

it('searches companies, customers and leads as subjects', function () {
    Company::factory()->create(['title' => 'Searchable Co']);

    $results = getJson(route('tasks.subjects', ['q' => 'Searchable']))->assertOk()->json();

    expect(collect($results)->contains(fn ($r) => $r['type'] === 'company'))->toBeTrue();
});
