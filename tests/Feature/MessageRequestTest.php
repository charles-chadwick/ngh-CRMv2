<?php

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actor = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->actor);
});

it('requires the core message fields on create', function () {
    $this->post(route('messages.store'), [])
        ->assertSessionHasErrors(['model_type', 'model_id', 'content']);
});

it('passes with a complete create payload', function () {
    $this->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Valid.</p>',
    ])->assertSessionHasNoErrors()->assertRedirect();
});

it('does not require a model when updating an existing message', function () {
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($this->actor)->create();

    $this->put(route('messages.update', $message->id), [
        'content' => '<p>Updated body.</p>',
    ])->assertSessionHasNoErrors()->assertRedirect();
});
