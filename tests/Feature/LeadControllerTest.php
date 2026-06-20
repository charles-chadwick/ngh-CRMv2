<?php

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('lists leads on the index page with their company', function () {
    $company = Company::factory()->create(['title' => 'Acme Roofing']);
    Lead::factory()->count(3)->create(['company_id' => $company->id]);

    $this->get(route('leads.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Index', false)
            ->has('leads.data', 3)
            ->where('leads.data.0.company.title', 'Acme Roofing')
        );
});

it('renders the create form', function () {
    $this->get(route('leads.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Form', false)
            ->has('statuses', 4)
            ->has('types', 5)
            ->has('users')
            ->has('companies')
        );
});

it('stores a new lead and redirects to show', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $company = Company::factory()->create();

    $response = $this->post(route('leads.store'), [
        'company_id' => $company->id,
        'title' => 'New roof lead',
        'type' => LeadType::Web->value,
        'status' => LeadStatus::New->value,
        'assigned_to' => $user->id,
        'initiated_at' => '2026-06-20',
    ]);

    $lead = Lead::firstWhere('title', 'New roof lead');

    expect($lead)->not->toBeNull()
        ->and($lead->company_id)->toBe($company->id)
        ->and($lead->assigned_at)->not->toBeNull();
    $response->assertRedirect(route('leads.show', $lead));
});

it('rejects an invalid status or type', function () {
    $this->post(route('leads.store'), [
        'title' => 'Bad lead',
        'type' => 'Carrier Pigeon',
        'status' => 'Pondering',
    ])->assertSessionHasErrors(['type', 'status']);
});

it('requires a title', function () {
    $this->post(route('leads.store'), [
        'type' => LeadType::Web->value,
        'status' => LeadStatus::New->value,
    ])->assertSessionHasErrors('title');
});

it('requires a company', function () {
    $this->post(route('leads.store'), [
        'title' => 'Companyless lead',
        'type' => LeadType::Web->value,
        'status' => LeadStatus::New->value,
    ])->assertSessionHasErrors('company_id');
});

it('shows a lead with notes and creation attribution', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $company = Company::factory()->create(['title' => 'Acme Roofing']);
    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $this->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Show', false)
            ->where('lead.company.title', 'Acme Roofing')
            ->where('lead.creation_activity.causer.full_name', $user->full_name)
            ->has('notes')
            ->has('note_types')
        );
});

it('renders the edit form for a lead', function () {
    $lead = Lead::factory()->create();

    $this->get(route('leads.edit', $lead))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Leads/Form', false)
            ->where('lead.id', $lead->id)
        );
});

it('updates a lead and redirects to show', function () {
    $lead = Lead::factory()->create(['title' => 'Old title']);

    $this->put(route('leads.update', $lead), [
        'company_id' => $lead->company_id,
        'title' => 'Updated title',
        'type' => $lead->type->value,
        'status' => LeadStatus::Qualified->value,
    ])->assertRedirect(route('leads.show', $lead));

    expect($lead->fresh()->status)->toBe(LeadStatus::Qualified);
});

it('soft deletes a lead and redirects to index', function () {
    $lead = Lead::factory()->create();

    $this->delete(route('leads.destroy', $lead))
        ->assertRedirect(route('leads.index'));

    expect($lead->fresh()->trashed())->toBeTrue();
});

it('accepts notes against a lead', function () {
    $lead = Lead::factory()->create();

    $this->post(route('notes.store'), [
        'model_type' => 'lead',
        'model_id' => $lead->id,
        'type' => 'General',
        'title' => 'First contact',
        'content' => 'Spoke with the prospect.',
    ])->assertRedirect();

    expect($lead->notes()->count())->toBe(1);
});
