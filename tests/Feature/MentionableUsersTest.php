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

it('does not return non-matching users when a search query is given', function () {
    $me = User::factory()->create(['first_name' => 'Me', 'last_name' => 'Self']);
    $rick = User::factory()->create(['first_name' => 'Rick', 'last_name' => 'Rickson']);
    $zelda = User::factory()->create(['first_name' => 'Zelda', 'last_name' => 'Zynn']);

    $this->actingAs($me)
        ->getJson(route('users.mentionable', ['q' => 'Rick']))
        ->assertOk()
        ->assertJsonFragment(['id' => $rick->id])
        ->assertJsonMissing(['id' => $zelda->id]);
});

it('excludes the current user from results', function () {
    $me = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Actor']);
    User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Other']);

    $this->actingAs($me)
        ->getJson(route('users.mentionable', ['q' => 'Alice']))
        ->assertOk()
        ->assertJsonMissing(['id' => $me->id]);
});

it('returns users when no search query is provided', function () {
    $me = User::factory()->create(['first_name' => 'Me', 'last_name' => 'Self']);
    $other = User::factory()->create(['first_name' => 'Other', 'last_name' => 'User']);

    $this->actingAs($me)
        ->getJson(route('users.mentionable'))
        ->assertOk()
        ->assertJsonFragment(['id' => $other->id]);
});

it('returns users when search query is whitespace', function () {
    $me = User::factory()->create(['first_name' => 'Me', 'last_name' => 'Self']);
    $other = User::factory()->create(['first_name' => 'Other', 'last_name' => 'User']);

    $this->actingAs($me)
        ->getJson(route('users.mentionable', ['q' => '   ']))
        ->assertOk()
        ->assertJsonFragment(['id' => $other->id]);
});
