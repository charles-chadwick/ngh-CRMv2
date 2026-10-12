<?php

use App\Models\Company;
use App\Models\CompanyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the create company form with types', function () {
    CompanyType::create(['name' => 'Airplane Parts']);

    $this->get(route('companies.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company/Form', false)
            ->where('company', null)
            ->has('types', 1)
        );
});

it('stores a company and redirects to it', function () {
    $type = CompanyType::create(['name' => 'Airplane Parts']);

    $response = $this->post(route('companies.store'), [
        'title' => 'Acme Aviation',
        'company_type_id' => $type->id,
        'description' => 'Wings and things.',
    ]);

    $company = Company::where('title', 'Acme Aviation')->firstOrFail();

    $response->assertRedirect(route('companies.show', $company));
    expect($company->company_type_id)->toBe($type->id)
        ->and($company->description)->toBe('Wings and things.');
});

it('requires a title to store a company', function () {
    $this->post(route('companies.store'), ['title' => ''])
        ->assertSessionHasErrors('title');

    expect(Company::count())->toBe(0);
});
