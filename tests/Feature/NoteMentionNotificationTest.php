<?php

use App\Models\Customer;
use App\Models\Note;
use App\Models\User;
use App\Notifications\UserMentionedInNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function postNoteMentioning(array $userIds, Customer $customer): array
{
    $spans = collect($userIds)
        ->map(fn (int $id): string => "<span data-id=\"{$id}\">@User</span>")
        ->implode(' ');

    return [
        'model_type' => 'customer',
        'model_id' => $customer->id,
        'type' => 'General',
        'title' => 'Note',
        'content' => "<p>hey {$spans}</p>",
    ];
}

it('notifies mentioned users but not the author', function () {
    Notification::fake();

    $author = User::factory()->create();
    $jane = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($author)
        ->post(route('notes.store'), postNoteMentioning([$jane->id, $author->id], $customer))
        ->assertRedirect();

    Notification::assertSentTo($jane, UserMentionedInNote::class);
    Notification::assertNotSentTo($author, UserMentionedInNote::class);
});

it('only notifies newly added mentions on update', function () {
    $author = User::factory()->create();
    $jane = User::factory()->create();
    $john = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($author)
        ->post(route('notes.store'), postNoteMentioning([$jane->id], $customer));

    $note = Note::firstOrFail();

    Notification::fake();

    $this->actingAs($author)
        ->put(route('notes.update', $note), postNoteMentioning([$jane->id, $john->id], $customer));

    Notification::assertSentTo($john, UserMentionedInNote::class);
    Notification::assertNotSentTo($jane, UserMentionedInNote::class);
});

it('does not throw when a guest posts a note with a mention', function () {
    Notification::fake();

    $mentioned = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->post(route('notes.store'), [
        'model_type' => 'customer',
        'model_id' => $customer->id,
        'type' => 'General',
        'title' => 'Guest note',
        'content' => "<p><span data-id=\"{$mentioned->id}\">@User</span></p>",
    ])->assertRedirect();

    $note = Note::firstOrFail();

    Notification::assertNothingSent();

    expect($note->mentionedUsers->contains($mentioned))->toBeTrue();
});
