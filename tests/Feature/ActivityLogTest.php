<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('logs a creation activity with no causer when unauthenticated', function () {
    $customer = Customer::factory()->create();

    $activity = $customer->creationActivity;

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->causer)->toBeNull();
});

it('records the authenticated user as the activity causer', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $customer = Customer::factory()->create();

    expect($customer->creationActivity->causer->is($user))->toBeTrue();
});

it('exposes the creation activity and causer on the customer show page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $customer = Customer::factory()->create();

    $this->get(route('customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customers/Show', false)
            ->where('customer.creation_activity.causer.full_name', $user->full_name)
        );
});
