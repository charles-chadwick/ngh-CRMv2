<?php

use App\Enum\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
});

it('renders the create user form with roles', function () {
    $this->get(route('users.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Users/Form')
            ->where('user', null)
            ->has('roles', 2)
        );
});

it('stores a user and assigns their permission role', function () {
    $this->post(route('users.store'), [
        'role' => UserRole::Staff->value,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertRedirect();

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Staff)
        ->and($user->hasRole(UserRole::Staff->value))->toBeTrue()
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
});

it('requires a password when creating a user', function () {
    $this->post(route('users.store'), [
        'role' => UserRole::Staff->value,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ])->assertSessionHasErrors('password');
});

it('rejects a duplicate email', function () {
    $existing = User::factory()->create();

    $this->post(route('users.store'), [
        'role' => UserRole::Staff->value,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => $existing->email,
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('email');
});
