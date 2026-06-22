<?php

use App\Models\Customer;
use App\Models\Message;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->actingAs($this->author);
});

it('starts a topic with user and customer participants and an opening message', function () {
    $teammate = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->post(route('topics.store'), [
        'title' => 'Project kickoff',
        'participants' => [
            ['type' => 'user', 'id' => $teammate->id],
            ['type' => 'customer', 'id' => $customer->id],
        ],
        'content' => '<p>Welcome aboard.</p>',
    ])->assertRedirect();

    $topic = Topic::firstOrFail();

    expect($topic->title)->toBe('Project kickoff')
        ->and($topic->users()->pluck('users.id'))->toContain($this->author->id, $teammate->id)
        ->and($topic->customers()->pluck('customers.id'))->toContain($customer->id)
        ->and($topic->messages()->count())->toBe(1);

    $message = $topic->messages()->first();

    expect($message->author->is($this->author))->toBeTrue()
        ->and($message->content)->toContain('Welcome aboard.');
});

it('shows a topic to a participant and marks it read', function () {
    $topic = Topic::factory()->create();
    $topic->users()->attach($this->author);
    Message::factory()->forTopic($topic)->byAuthor(Customer::factory()->create())->create();

    $this->get(route('topics.show', $topic))->assertOk();

    expect($topic->users()->find($this->author->id)->pivot->last_read_at)->not->toBeNull();
});

it('forbids a non-participant from viewing a topic', function () {
    $topic = Topic::factory()->create();
    $topic->users()->attach(User::factory()->create());

    $this->get(route('topics.show', $topic))->assertForbidden();
});
