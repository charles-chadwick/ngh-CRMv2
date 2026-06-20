<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('stores a contact and makes the first one primary automatically', function () {
    $customer = Customer::factory()->create();

    $this->post(route('contacts.store'), [
        'model_type' => 'customer',
        'model_id' => $customer->id,
        'type' => 'Office',
        'city' => 'Seattle',
    ])->assertRedirect();

    $contact = Contact::firstOrFail();

    expect($contact->contactable->is($customer))->toBeTrue()
        ->and($contact->is_primary)->toBeTrue()
        ->and($contact->city)->toBe('Seattle');
});

it('stores a contact against a company', function () {
    $company = Company::factory()->create();

    $this->post(route('contacts.store'), [
        'model_type' => 'company',
        'model_id' => $company->id,
        'type' => 'Billing',
    ])->assertRedirect();

    expect($company->contacts()->count())->toBe(1);
});

it('keeps only one primary contact per parent', function () {
    $customer = Customer::factory()->create();
    $first = Contact::factory()->forContactable($customer)->primary()->create();
    $second = Contact::factory()->forContactable($customer)->create();

    $this->patch(route('contacts.set-primary', $second))->assertRedirect();

    expect($second->fresh()->is_primary)->toBeTrue()
        ->and($first->fresh()->is_primary)->toBeFalse()
        ->and($customer->contacts()->where('is_primary', true)->count())->toBe(1);
});

it('updates a contact', function () {
    $customer = Customer::factory()->create();
    $contact = Contact::factory()->forContactable($customer)->create(['city' => 'Old']);

    $this->patch(route('contacts.update', $contact), [
        'type' => 'Mailing',
        'city' => 'New',
    ])->assertRedirect();

    expect($contact->fresh()->city)->toBe('New')
        ->and($contact->fresh()->type)->toBe('Mailing');
});

it('deletes a contact', function () {
    $customer = Customer::factory()->create();
    $contact = Contact::factory()->forContactable($customer)->create();

    $this->delete(route('contacts.destroy', $contact))->assertRedirect();

    expect(Contact::count())->toBe(0);
});

it('rejects an invalid type', function () {
    $customer = Customer::factory()->create();

    $this->post(route('contacts.store'), [
        'model_type' => 'customer',
        'model_id' => $customer->id,
        'type' => 'Bogus',
    ])->assertSessionHasErrors('type');
});
