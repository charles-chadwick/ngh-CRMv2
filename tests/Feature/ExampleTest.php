<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application returns a successful response', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get('/');

    $response->assertStatus(200);
});
