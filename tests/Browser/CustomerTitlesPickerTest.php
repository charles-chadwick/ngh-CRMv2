<?php

use App\Models\Customer;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->customer = Customer::factory()->create();

    Title::factory()->create(['title' => 'Founder']);
    Title::factory()->create(['title' => 'Investor']);
});

it('adds a badge for each title picked from the dropdown', function () {
    $page = visit(route('customers.edit', $this->customer));

    $page->assertSee('Add a title')
        ->click('#titles')
        ->click('Founder')
        ->assertSeeIn('#titles', 'Add a title')
        ->click('#titles')
        ->click('Investor')
        ->assertSeeIn('#titles', 'All titles added')
        ->assertVisible('[aria-label="Remove Founder"]')
        ->assertVisible('[aria-label="Remove Investor"]')
        ->assertNoJavaScriptErrors();
});

it('saves the picked titles', function () {
    $page = visit(route('customers.edit', $this->customer));

    $page->click('#titles')
        ->click('Founder')
        ->click('Save Customer')
        ->assertPathIs('/customers/'.$this->customer->id)
        ->assertNoJavaScriptErrors();

    expect($this->customer->titles()->pluck('title')->all())->toBe(['Founder']);
});

it('drops a title when its badge is dismissed', function () {
    $this->customer->titles()->attach(Title::pluck('id'));

    $page = visit(route('customers.edit', $this->customer));

    $page->assertVisible('[aria-label="Remove Founder"]')
        ->click('[aria-label="Remove Founder"]')
        ->assertMissing('[aria-label="Remove Founder"]')
        ->assertVisible('[aria-label="Remove Investor"]')
        ->click('Save Customer')
        ->assertNoJavaScriptErrors();

    expect($this->customer->titles()->pluck('title')->all())->toBe(['Investor']);
});
