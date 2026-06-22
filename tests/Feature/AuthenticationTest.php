<?php

use App\Models\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('renders the login page for guests', function () {
    get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('redirects guests to login when visiting a protected page', function () {
    get(route('home'))->assertRedirect(route('login'));
});

it('authenticates a user with valid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
    ]);

    post(route('login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ])->assertRedirect(route('home'));

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

it('records a login when authentication succeeds', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
    ]);

    post(route('login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    assertDatabaseHas(Login::class, ['user_id' => $user->id]);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
    ]);

    post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('redirects authenticated users away from the login page', function () {
    actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect();
});
