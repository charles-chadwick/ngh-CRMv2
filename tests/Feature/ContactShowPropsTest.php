<?php

use App\Models\Company;
use App\Models\CompanyType;
use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('passes contacts and contact types to the customer show page', function () {
    $customer = Customer::factory()->create();
    Contact::factory()->forContactable($customer)->primary()->create();

    $this->get(route('customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customers/Show', false)
            ->has('contacts.data', 1)
            ->where('contacts.data.0.is_primary', true)
            ->has('contacts.last_page')
            ->has('contact_types')
        );
});

it('passes contacts and contact types to the company show page', function () {
    CompanyType::create(['name' => 'Airplane Parts']);
    $company = Company::factory()->create();
    Contact::factory()->forContactable($company)->create();

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Company/Show', false)
            ->has('contacts.data', 1)
            ->has('contacts.last_page')
            ->has('contact_types')
        );
});
