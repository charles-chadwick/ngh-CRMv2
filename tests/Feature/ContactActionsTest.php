<?php

use App\Actions\Contacts\CreateContact;
use App\Actions\Contacts\DeleteContact;
use App\Actions\Contacts\SetPrimaryContact;
use App\Actions\Contacts\UpdateContact;
use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a contact and makes the first one primary automatically', function () {
    $customer = Customer::factory()->create();

    $contact = CreateContact::run($customer, ['type' => 'Office', 'city' => 'Seattle']);

    expect($contact->contactable->is($customer))->toBeTrue()
        ->and($contact->is_primary)->toBeTrue()
        ->and($contact->city)->toBe('Seattle');
});

it('does not promote a second contact unless requested', function () {
    $customer = Customer::factory()->create();
    Contact::factory()->forContactable($customer)->primary()->create();

    $second = CreateContact::run($customer, ['type' => 'Billing']);

    expect($second->fresh()->is_primary)->toBeFalse();
});

it('promotes a new contact to primary when requested', function () {
    $customer = Customer::factory()->create();
    $first = Contact::factory()->forContactable($customer)->primary()->create();

    $second = CreateContact::run($customer, ['type' => 'Billing'], is_primary: true);

    expect($second->fresh()->is_primary)->toBeTrue()
        ->and($first->fresh()->is_primary)->toBeFalse();
});

it('updates a contact without touching primary', function () {
    $customer = Customer::factory()->create();
    $contact = Contact::factory()->forContactable($customer)->create(['city' => 'Old']);

    UpdateContact::run($contact, ['type' => 'Mailing', 'city' => 'New']);

    expect($contact->fresh()->city)->toBe('New')
        ->and($contact->fresh()->type)->toBe('Mailing');
});

it('updates a contact and promotes it to primary when requested', function () {
    $customer = Customer::factory()->create();
    $primary = Contact::factory()->forContactable($customer)->primary()->create();
    $other = Contact::factory()->forContactable($customer)->create();

    UpdateContact::run($other, ['city' => 'New'], is_primary: true);

    expect($other->fresh()->is_primary)->toBeTrue()
        ->and($primary->fresh()->is_primary)->toBeFalse();
});

it('keeps only one primary contact per parent', function () {
    $customer = Customer::factory()->create();
    $first = Contact::factory()->forContactable($customer)->primary()->create();
    $second = Contact::factory()->forContactable($customer)->create();

    SetPrimaryContact::run($second);

    expect($second->fresh()->is_primary)->toBeTrue()
        ->and($first->fresh()->is_primary)->toBeFalse()
        ->and($customer->contacts()->where('is_primary', true)->count())->toBe(1);
});

it('promotes a sibling when the primary contact is deleted', function () {
    $customer = Customer::factory()->create();
    $primary = Contact::factory()->forContactable($customer)->primary()->create();
    $other = Contact::factory()->forContactable($customer)->create();

    DeleteContact::run($primary);

    expect($other->fresh()->is_primary)->toBeTrue()
        ->and($customer->contacts()->where('is_primary', true)->count())->toBe(1);
});

it('leaves no primary when the last contact is deleted', function () {
    $customer = Customer::factory()->create();
    $contact = Contact::factory()->forContactable($customer)->primary()->create();

    DeleteContact::run($contact);

    expect($customer->contacts()->count())->toBe(0);
});
