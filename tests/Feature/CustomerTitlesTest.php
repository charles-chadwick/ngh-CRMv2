<?php

use App\Models\Customer;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('joins the attached titles into a single display string', function () {
    $customer = Customer::factory()->create();
    $customer->titles()->attach(
        collect(['Founder', 'Product Manager', 'UX Designer'])
            ->map(fn (string $title) => Title::factory()->create(['title' => $title])->id)
    );

    expect($customer->fresh()->title)->toBe('Founder / Product Manager / UX Designer');
});

it('has an empty title when no titles are attached', function () {
    expect(Customer::factory()->create()->title)->toBe('');
});

it('exposes the joined title on the customer show page', function () {
    $customer = Customer::factory()->create();
    $customer->titles()->attach([
        Title::factory()->create(['title' => 'Founder'])->id,
        Title::factory()->create(['title' => 'Investor'])->id,
    ]);

    $this->get(route('customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customers/Show', false)
            ->where('customer.title', 'Founder / Investor')
        );
});

it('lists the selectable titles on the edit page', function () {
    $customer = Customer::factory()->create();
    $title = Title::factory()->create(['title' => 'Founder']);
    Title::factory()->create(['title' => 'Consultant']);
    $customer->titles()->attach($title);

    $this->get(route('customers.edit', $customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customers/Form', false)
            ->has('titles', 2)
            ->has('customer.titles', 1)
            ->where('customer.titles.0.id', $title->id)
        );
});

it('syncs the selected titles when a customer is updated', function () {
    $customer = Customer::factory()->create();
    $old = Title::factory()->create();
    $kept = Title::factory()->create();
    $added = Title::factory()->create();
    $customer->titles()->attach([$old->id, $kept->id]);

    $this->put(route('customers.update', $customer), [
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'email' => $customer->email,
        'titles' => [$kept->id, $added->id],
    ])->assertRedirect(route('customers.show', $customer));

    expect($customer->titles()->pluck('titles.id')->all())
        ->toEqualCanonicalizing([$kept->id, $added->id]);
});

it('detaches every title when none are selected', function () {
    $customer = Customer::factory()->create();
    $customer->titles()->attach(Title::factory()->create());

    $this->put(route('customers.update', $customer), [
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'email' => $customer->email,
    ])->assertRedirect(route('customers.show', $customer));

    expect($customer->titles()->count())->toBe(0);
});

it('attaches the selected titles when a customer is created', function () {
    $titles = Title::factory()->count(2)->create();

    $this->post(route('customers.store'), [
        'first_name' => 'Beth',
        'last_name' => 'Smith',
        'email' => 'beth.smith@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'titles' => $titles->pluck('id')->all(),
    ])->assertRedirect();

    $customer = Customer::where('email', 'beth.smith@example.com')->firstOrFail();

    expect($customer->titles()->pluck('titles.id')->all())
        ->toEqualCanonicalizing($titles->pluck('id')->all());
});

it('rejects titles that do not exist', function () {
    $customer = Customer::factory()->create();

    $this->put(route('customers.update', $customer), [
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'email' => $customer->email,
        'titles' => [999],
    ])->assertSessionHasErrors('titles.0');
});
