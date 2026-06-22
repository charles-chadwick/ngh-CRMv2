<?php

use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->actingAs($this->author);
});

it('creates the conversation topic and posts the first message', function () {
    $customer = Customer::factory()->create();

    $this->post(route('customers.messages.store', $customer), [
        'content' => '<p>Welcome to your portal.</p>',
    ])->assertRedirect();

    $topic = $customer->conversation()->first();

    expect($topic)->not->toBeNull()
        ->and($topic->customer_id)->toBe($customer->id)
        ->and($topic->customers()->pluck('customers.id'))->toContain($customer->id)
        ->and($topic->users()->pluck('users.id'))->toContain($this->author->id)
        ->and($topic->messages()->count())->toBe(1);

    $message = Message::firstOrFail();

    expect($message->author->is($this->author))->toBeTrue()
        ->and($message->content)->toContain('Welcome to your portal.');
});

it('reuses the same conversation for later messages', function () {
    $customer = Customer::factory()->create();

    $this->post(route('customers.messages.store', $customer), ['content' => '<p>First.</p>']);
    $this->post(route('customers.messages.store', $customer), ['content' => '<p>Second.</p>']);

    expect($customer->conversation()->count())->toBe(1)
        ->and($customer->paginatedMessages()->total())->toBe(2);
});

it('shows the customer conversation messages on the customer page', function () {
    $customer = Customer::factory()->create();

    $this->post(route('customers.messages.store', $customer), ['content' => '<p>Hello there.</p>']);

    $this->get(route('customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Customers/Show')
            ->has('messages.data', 1)
        );
});
