<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('orders contacts with the primary first', function () {
    $customer = Customer::factory()->create();
    Contact::factory()->forContactable($customer)->create(['type' => 'Billing']);
    $primary = Contact::factory()->forContactable($customer)->primary()->create(['type' => 'Office']);

    expect($customer->contacts()->first()->is($primary))->toBeTrue()
        ->and($customer->primaryContact->is($primary))->toBeTrue();
});

it('attaches contacts to a company', function () {
    $company = Company::factory()->create();
    Contact::factory()->forContactable($company)->create();

    expect($company->contacts()->count())->toBe(1);
});
