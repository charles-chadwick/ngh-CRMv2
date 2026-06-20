<?php

use App\Enum\LeadStatus;
use App\Enum\LeadType;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts status and type to enums', function () {
    $lead = Lead::factory()->create();

    expect($lead->status)->toBeInstanceOf(LeadStatus::class)
        ->and($lead->type)->toBeInstanceOf(LeadType::class);
});

it('mass-assigns the fillable lead attributes', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $lead = Lead::create([
        'company_id' => $company->id,
        'title' => 'Roof replacement',
        'type' => LeadType::Web,
        'status' => LeadStatus::New,
        'assigned_to' => $user->id,
        'initiated_at' => now(),
    ]);

    expect($lead->title)->toBe('Roof replacement')
        ->and($lead->company_id)->toBe($company->id)
        ->and($lead->assigned_to)->toBe($user->id);
});

it('sets assigned_at when a lead is assigned', function () {
    $user = User::factory()->create();

    $lead = Lead::factory()->create(['assigned_to' => null]);
    expect($lead->assigned_at)->toBeNull();

    $lead->update(['assigned_to' => $user->id]);

    expect($lead->fresh()->assigned_at)->not->toBeNull();
});

it('clears assigned_at when a lead is unassigned', function () {
    $lead = Lead::factory()->create(['assigned_to' => User::factory()]);
    expect($lead->assigned_at)->not->toBeNull();

    $lead->update(['assigned_to' => null]);

    expect($lead->fresh()->assigned_at)->toBeNull();
});

it('searches leads by normalized title', function () {
    Lead::factory()->create(['title' => 'Acme Roofing']);
    Lead::factory()->create(['title' => 'Beta Siding']);

    $results = Lead::query()->withSearch('Acme')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('Acme Roofing');
});
