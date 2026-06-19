<?php

use App\Models\Customer;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records the authenticated user as the note creator', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'model_type' => 'customer',
            'model_id' => $customer->id,
            'type' => 'General',
            'title' => 'Test note',
            'content' => 'Note body.',
        ])
        ->assertRedirect();

    $note = Note::firstOrFail();

    expect($note->creationActivity->causer->is($user))->toBeTrue();
});

it('eager loads the note creator through paginatedNotes', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'model_type' => 'customer',
            'model_id' => $customer->id,
            'type' => 'General',
            'title' => 'Test note',
            'content' => 'Note body.',
        ]);

    $note = $customer->paginatedNotes()->first();

    expect($note->relationLoaded('creationActivity'))->toBeTrue()
        ->and($note->creationActivity->causer->id)->toBe($user->id);
});
