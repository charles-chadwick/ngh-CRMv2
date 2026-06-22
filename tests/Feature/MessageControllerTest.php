<?php

use App\Events\MessagePosted;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->author);
});

it('stores a message against a customer and attributes the author', function () {
    $this->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Hello there.</p>',
    ])->assertRedirect();

    $message = Message::firstOrFail();

    expect($message->messageable->is($this->customer))->toBeTrue()
        ->and($message->author->is($this->author))->toBeTrue()
        ->and($message->content)->toContain('Hello there.');
});

it('broadcasts MessagePosted on the customer channel', function () {
    Event::fake([MessagePosted::class]);

    $this->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Ping.</p>',
    ])->assertRedirect();

    Event::assertDispatched(MessagePosted::class, function (MessagePosted $event) {
        $channels = $event->broadcastOn();

        return $channels[0] instanceof PrivateChannel
            && $channels[0]->name === 'private-customers.'.$this->customer->id;
    });
});

it('lets a customer author a message (two-sided)', function () {
    $this->actingAs($this->customer, 'customer')->post(route('messages.store'), [
        'model_type' => 'customer',
        'model_id' => $this->customer->id,
        'content' => '<p>Reply from the customer.</p>',
    ])->assertRedirect();

    expect(Message::firstOrFail()->author)->toBeInstanceOf(Customer::class);
});

it('rejects an unknown model type', function () {
    $this->post(route('messages.store'), [
        'model_type' => 'lead',
        'model_id' => $this->customer->id,
        'content' => '<p>Nope.</p>',
    ])->assertSessionHasErrors('model_type');

    expect(Message::count())->toBe(0);
});

it('updates the actor\'s own message', function () {
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($this->author)->create();

    $this->put(route('messages.update', $message->id), [
        'content' => '<p>Edited.</p>',
    ])->assertRedirect();

    expect($message->fresh()->content)->toContain('Edited.');
});

it('forbids editing another actor\'s message', function () {
    $other = User::factory()->create();
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($other)->create();

    $this->put(route('messages.update', $message->id), [
        'content' => '<p>Hijack.</p>',
    ])->assertForbidden();
});

it('deletes the actor\'s own message', function () {
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($this->author)->create();

    $this->delete(route('messages.destroy', $message->id))->assertRedirect();

    expect($message->fresh()->trashed())->toBeTrue();
});

it('forbids deleting another actor\'s message', function () {
    $other = User::factory()->create();
    $message = Message::factory()->forMessageable($this->customer)->byAuthor($other)->create();

    $this->delete(route('messages.destroy', $message->id))->assertForbidden();

    expect($message->fresh()->trashed())->toBeFalse();
});

it('includes the customer messages on the show page', function () {
    Message::factory()->forMessageable($this->customer)->byAuthor($this->author)->create();

    $this->get(route('customers.show', $this->customer->id))
        ->assertInertia(fn ($page) => $page
            ->component('Customers/Show')
            ->has('messages.data', 1));
});
