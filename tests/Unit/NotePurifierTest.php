<?php

use App\Services\NoteContentSanitizer;
use Mews\Purifier\Facades\Purifier;
use Tests\TestCase;

uses(TestCase::class);

it('strips scripts and event handlers but keeps mention spans', function () {
    $dirty = '<p>hi <span class="mention" data-id="5">@Jane</span></p>'
        .'<script>alert(1)</script><img src=x onerror="alert(1)">';

    $clean = (new NoteContentSanitizer)->clean($dirty);

    expect($clean)->toContain('data-id="5"')
        ->and($clean)->not->toContain('<script')
        ->and($clean)->not->toContain('onerror')
        ->and($clean)->not->toContain('<img');
});

it('does not leak data-id through the default purifier profile', function () {
    $dirty = '<span data-id="9">x</span>';

    $clean = Purifier::clean($dirty, 'default');

    expect($clean)->not->toContain('data-id');
});
