<?php

use App\Models\Customer;
use App\Models\Note;
use App\Models\User;
use App\Notifications\UserMentionedInNote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks a single notification as read', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for(Customer::factory(), 'notable')->create();
    $user->notify(new UserMentionedInNote($note, User::factory()->create()));

    $id = $user->unreadNotifications()->first()->id;

    $this->actingAs($user)
        ->patch(route('notifications.read', $id))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications as read', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for(Customer::factory(), 'notable')->create();
    $user->notify(new UserMentionedInNote($note, User::factory()->create()));
    $user->notify(new UserMentionedInNote($note, User::factory()->create()));

    $this->actingAs($user)
        ->patch(route('notifications.readAll'))
        ->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
