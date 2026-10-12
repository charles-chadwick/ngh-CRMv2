<?php

use App\Models\Company;
use App\Models\CompanyType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    CompanyType::create(['name' => 'Airplane Parts']);
});

/**
 * @return array<string, mixed>
 */
function newCustomerPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        ...$overrides,
    ];
}

it('renders the create customer form with companies and the preselected company', function () {
    $company = Company::factory()->create();

    $this->get(route('customers.create', ['company_id' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Customers/Form', false)
            ->where('customer', null)
            ->where('company_id', $company->id)
            ->has('companies', 1)
            ->has('roles')
        );
});

it('stores a customer for a company and redirects to them', function () {
    $company = Company::factory()->create();

    $response = $this->post(route('customers.store'), newCustomerPayload(['company_id' => $company->id]));

    $customer = Customer::where('email', 'jane@example.com')->firstOrFail();

    $response->assertRedirect(route('customers.show', $customer));
    expect($customer->company_id)->toBe($company->id)
        ->and(Hash::check('secret-password', $customer->password))->toBeTrue();
});

it('requires a password to store a customer', function () {
    $this->post(route('customers.store'), newCustomerPayload([
        'password' => '',
        'password_confirmation' => '',
    ]))->assertSessionHasErrors('password');

    expect(Customer::count())->toBe(0);
});

it('rejects a duplicate customer email', function () {
    Customer::factory()->create(['email' => 'jane@example.com']);

    $this->post(route('customers.store'), newCustomerPayload())
        ->assertSessionHasErrors('email');
});
