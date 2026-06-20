<?php

use Mews\Purifier\Facades\Purifier;
use Tests\TestCase;

uses(TestCase::class);

it('strips scripts and event handlers but keeps mention spans', function () {
    $dirty = '<p>hi <span class="mention" data-id="5">@Jane</span></p>'
        .'<script>alert(1)</script><img src=x onerror="alert(1)">';

    $clean = Purifier::clean($dirty, 'notes');

    expect($clean)->toContain('data-id="5"')
        ->and($clean)->not->toContain('<script')
        ->and($clean)->not->toContain('onerror')
        ->and($clean)->not->toContain('<img');
});
