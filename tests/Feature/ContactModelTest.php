<?php

use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches a contact to a customer polymorphically and casts is_primary', function () {
    $customer = Customer::factory()->create();

    $contact = Contact::factory()->forContactable($customer)->primary()->create();

    expect($contact->contactable->is($customer))->toBeTrue()
        ->and($contact->is_primary)->toBeTrue()
        ->and(in_array($contact->type, Contact::TYPES, true))->toBeTrue();
});
