<?php

use App\Events\MessagePosted;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->topic = Topic::factory()->create();
    $this->topic->users()->attach($this->author);
    $this->actingAs($this->author);
});

it('posts a message to a topic the participant belongs to', function () {
    $this->post(route('messages.store'), [
        'topic_id' => $this->topic->id,
        'content' => '<p>Hello there.</p>',
    ])->assertRedirect();

    $message = Message::firstOrFail();

    expect($message->topic->is($this->topic))->toBeTrue()
        ->and($message->author->is($this->author))->toBeTrue()
        ->and($message->content)->toContain('Hello there.');
});

it('forbids a non-participant from posting', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->post(route('messages.store'), [
        'topic_id' => $this->topic->id,
        'content' => '<p>Let me in.</p>',
    ])->assertForbidden();

    expect(Message::count())->toBe(0);
});

it('broadcasts MessagePosted on the topic channel', function () {
    Event::fake([MessagePosted::class]);

    $this->post(route('messages.store'), [
        'topic_id' => $this->topic->id,
        'content' => '<p>Ping.</p>',
    ])->assertRedirect();

    Event::assertDispatched(MessagePosted::class, function (MessagePosted $event) {
        $channels = $event->broadcastOn();

        return $channels[0] instanceof PrivateChannel
            && $channels[0]->name === 'private-topics.'.$this->topic->id;
    });
});

it('counts unread messages for a participant excluding their own', function () {
    $customer = Customer::factory()->create();
    $this->topic->customers()->attach($customer);

    Message::factory()->forTopic($this->topic)->byAuthor($customer)->create();
    Message::factory()->forTopic($this->topic)->byAuthor($this->author)->create();

    expect($this->topic->unreadCountFor($this->author))->toBe(1);

    $this->topic->markReadFor($this->author);

    expect($this->topic->fresh()->unreadCountFor($this->author))->toBe(0);
});
