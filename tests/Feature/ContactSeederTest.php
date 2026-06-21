<?php

use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\ContactSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attributes seeded contacts to an existing user as the activity causer', function () {
    $user = User::factory()->create();
    Customer::factory()->create();

    (new ContactSeeder)->run();

    $contact = Contact::with('creationActivity.causer')->first();

    expect($contact)->not->toBeNull()
        ->and($contact->creationActivity)->not->toBeNull()
        ->and($contact->creationActivity->causer->is($user))->toBeTrue();
});

it('still seeds contacts when no users exist to attribute them to', function () {
    Customer::factory()->create();

    expect(User::count())->toBe(0);

    (new ContactSeeder)->run();

    expect(Contact::count())->toBeGreaterThan(0);
});
