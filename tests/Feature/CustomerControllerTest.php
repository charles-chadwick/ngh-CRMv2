<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('deletes a customer and returns to the previous page', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);

    $this->from(route('companies.show', $company))
        ->delete(route('customers.destroy', $customer))
        ->assertRedirect(route('companies.show', $company));

    $this->assertSoftDeleted($customer);
});

it('requires authentication to delete a customer', function () {
    auth()->logout();

    $customer = Customer::factory()->create();

    $this->delete(route('customers.destroy', $customer))
        ->assertRedirect(route('login'));

    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});
