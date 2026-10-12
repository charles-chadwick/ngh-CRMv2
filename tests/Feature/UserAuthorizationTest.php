<?php

use App\Enum\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * @return array<string, string>
 */
function newUserPayload(UserRole $role): array
{
    return [
        'role' => $role->value,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ];
}

it('forbids staff from creating users', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]));

    $this->get(route('users.create'))->assertForbidden();
    $this->post(route('users.store'), newUserPayload(UserRole::Staff))->assertForbidden();

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('forbids staff from editing users, including themselves', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $other = User::factory()->create(['role' => UserRole::Staff]);
    $this->actingAs($staff);

    $this->get(route('users.edit', $other))->assertForbidden();
    $this->put(route('users.update', $staff), [
        ...newUserPayload(UserRole::SuperAdmin),
        'email' => $staff->email,
    ])->assertForbidden();

    expect($staff->fresh()->role)->toBe(UserRole::Staff);
});

it('prevents an admin from assigning the super admin role', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $this->post(route('users.store'), newUserPayload(UserRole::SuperAdmin))
        ->assertSessionHasErrors('role');

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('prevents an admin from editing a super admin', function () {
    $super_admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $this->get(route('users.edit', $super_admin))->assertForbidden();
    $this->put(route('users.update', $super_admin), [
        ...newUserPayload(UserRole::Admin),
        'email' => $super_admin->email,
    ])->assertForbidden();

    expect($super_admin->fresh()->role)->toBe(UserRole::SuperAdmin);
});

it('lets an admin edit staff', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $this->put(route('users.update', $staff), [
        ...newUserPayload(UserRole::Admin),
        'email' => $staff->email,
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect(route('users.show', $staff));

    expect($staff->fresh()->role)->toBe(UserRole::Admin);
});

it('lets a super admin create a super admin', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

    $this->post(route('users.store'), newUserPayload(UserRole::SuperAdmin))->assertRedirect();

    expect(User::where('email', 'jane@example.com')->firstOrFail()->role)->toBe(UserRole::SuperAdmin);
});

it('only offers roles at or below the current user', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $this->get(route('users.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles', UserRole::optionsAssignableBy(UserRole::Admin))
            ->has('roles', 2)
        );
});

it('only shows the create and edit buttons to users who may use them', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $this->actingAs($staff);

    $this->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page->where('can_create_user', false));
    $this->get(route('users.show', $staff))
        ->assertInertia(fn (Assert $page) => $page->where('can_update_user', false));
});
