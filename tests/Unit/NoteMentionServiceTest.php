<?php

use App\Models\Note;
use App\Models\User;
use App\Services\NoteMentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('syncs mentioned users and returns only newly added ones', function () {
    $note = Note::factory()->create();
    $jane = User::factory()->create();
    $john = User::factory()->create();
    $author = User::factory()->create();

    $service = new NoteMentionService;

    $html = sprintf(
        '<p>hi <span data-id="%d">@Jane</span> and <span data-id="%d">@Me</span></p>',
        $jane->id,
        $author->id,
    );

    $added = $service->syncFromContent($note, $html, $author->id);

    expect($added->pluck('id'))->toContain($jane->id)
        ->and($added->pluck('id'))->not->toContain($author->id)
        ->and($note->mentionedUsers()->pluck('users.id'))->toContain($jane->id);

    // Editing to add John returns only John.
    $html2 = $html.sprintf('<span data-id="%d">@John</span>', $john->id);
    $added2 = $service->syncFromContent($note, $html2, $author->id);

    expect($added2->pluck('id'))->toEqual(collect([$john->id]));
});
