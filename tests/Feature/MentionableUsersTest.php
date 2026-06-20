<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns matching users excluding the current user', function () {
    $me = User::factory()->create(['first_name' => 'Me', 'last_name' => 'Self']);
    $jane = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $this->actingAs($me)
        ->getJson(route('users.mentionable', ['q' => 'Jane']))
        ->assertOk()
        ->assertJsonFragment(['id' => $jane->id, 'full_name' => 'Jane Doe'])
        ->assertJsonMissing(['id' => $me->id]);
});
