<?php

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches a message to a customer and a user author', function () {
    $customer = Customer::factory()->create();
    $author = User::factory()->create();

    $message = Message::factory()->forMessageable($customer)->byAuthor($author)->create();

    expect($message->messageable->is($customer))->toBeTrue()
        ->and($message->author->is($author))->toBeTrue();
});

it('can be authored by a customer', function () {
    $customer = Customer::factory()->create();

    $message = Message::factory()->forMessageable($customer)->byAuthor($customer)->create();

    expect($message->author)->toBeInstanceOf(Customer::class);
});
