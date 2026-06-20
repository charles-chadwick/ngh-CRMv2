<?php

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('relates a note to its mentioned users', function () {
    $note = Note::factory()->create();
    $user = User::factory()->create();

    $note->mentionedUsers()->attach($user);

    expect($note->mentionedUsers()->pluck('users.id'))->toContain($user->id);
});
