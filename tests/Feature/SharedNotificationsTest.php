<?php

use App\Models\Customer;
use App\Models\Note;
use App\Models\User;
use App\Notifications\UserMentionedInNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('shares unread notifications with the frontend', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for(Customer::factory(), 'notable')->create();
    $user->notify(new UserMentionedInNote($note, User::factory()->create()));

    $this->actingAs($user)
        ->get(route('customers.show', Customer::factory()->create()))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.id', $user->id)
            ->where('notifications.unread_count', 1)
        );
});
