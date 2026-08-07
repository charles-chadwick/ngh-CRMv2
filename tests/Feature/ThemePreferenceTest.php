<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('applies the stored theme before first paint', function () {
    $html = actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain("localStorage.getItem('crm-theme')")
        ->toContain('(prefers-color-scheme: dark)')
        ->toContain("classList.toggle('dark', dark)");
});

it('runs the theme bootstrap before the app renders', function () {
    $html = get(route('login'))->assertOk()->getContent();

    expect(strpos($html, "localStorage.getItem('crm-theme')"))
        ->toBeLessThan(strpos($html, '<body'));
});
