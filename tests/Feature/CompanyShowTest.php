<?php

use App\Models\Company;
use App\Models\CompanyType;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    CompanyType::create(['name' => 'Airplane Parts']);
});

it('paginates the customers on the company show page', function () {
    $company = Company::factory()->create();
    Customer::factory()->count(11)->create(['company_id' => $company->id]);

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Company/Show', false)
            ->has('customers.data', 9)
            ->where('customers.total', 11)
            ->where('customers.per_page', 9)
            ->where('customers.last_page', 2)
        );
});

it('returns the remaining customers on the second page', function () {
    $company = Company::factory()->create();
    Customer::factory()->count(11)->create(['company_id' => $company->id]);

    $this->get(route('companies.show', [$company, 'customers_page' => 2]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('customers.data', 2)
            ->where('customers.current_page', 2)
        );
});
