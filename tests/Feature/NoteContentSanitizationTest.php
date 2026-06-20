<?php

use App\Models\Customer;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sanitizes note content on store', function () {
    $customer = Customer::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('notes.store'), [
            'model_type' => 'customer',
            'model_id' => $customer->id,
            'type' => 'General',
            'title' => 'Note',
            'content' => '<p>ok</p><script>alert(1)</script>',
        ])
        ->assertRedirect();

    expect(Note::firstOrFail()->content)
        ->toContain('<p>ok</p>')
        ->not->toContain('<script');
});

it('sanitizes note content on update', function () {
    $note = Note::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('notes.update', $note), [
            'type' => 'General',
            'title' => 'Updated',
            'content' => '<p>clean</p><script>alert(1)</script>',
        ])
        ->assertRedirect();

    expect($note->refresh()->content)
        ->toContain('<p>clean</p>')
        ->not->toContain('<script');
});
