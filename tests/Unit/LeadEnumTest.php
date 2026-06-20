<?php

use App\Enum\LeadStatus;
use App\Enum\LeadType;

it('exposes the lead status values', function () {
    expect(array_map(fn (LeadStatus $s) => $s->value, LeadStatus::cases()))
        ->toBe(['New', 'Working', 'Qualified', 'Closed']);
});

it('exposes the lead type values', function () {
    expect(array_map(fn (LeadType $t) => $t->value, LeadType::cases()))
        ->toBe(['Web', 'Phone', 'Email', 'Event', 'Other']);
});

it('builds dropdown options for statuses', function () {
    expect(LeadStatus::options())->toBe([
        ['value' => 'New', 'label' => 'New'],
        ['value' => 'Working', 'label' => 'Working'],
        ['value' => 'Qualified', 'label' => 'Qualified'],
        ['value' => 'Closed', 'label' => 'Closed'],
    ]);
});
