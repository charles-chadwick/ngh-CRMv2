<?php

namespace App\Services;

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves the users mentioned in a note's sanitized HTML and keeps the
 * note's `mentions` pivot in sync. The note content is the single source of
 * truth — IDs are parsed from `span[data-id]` tokens, never trusted from the
 * client directly.
 */
class NoteMentionService
{
    /**
     * Sync the note's mentioned users from its HTML content.
     *
     * @return Collection<int, User> the users newly mentioned by this sync
     */
    public function syncFromContent(Note $note, string $html, ?int $excludeUserId = null): Collection
    {
        $ids = $this->extractUserIds($html)
            ->reject(fn (int $id): bool => $id === $excludeUserId);

        $valid_ids = User::query()->whereKey($ids)->pluck('id');

        // sync() reports exactly which ids it attached, so the newly mentioned
        // users are derived from its result rather than a separate pivot query.
        $changes = $note->mentionedUsers()->sync($valid_ids);

        return User::query()
            ->whereKey($changes['attached'])
            ->get();
    }

    /**
     * Parse distinct user IDs from `data-id` attributes in the content.
     *
     * @return Collection<int, int>
     */
    protected function extractUserIds(string $html): Collection
    {
        preg_match_all('/data-id="(\d+)"/', $html, $matches);

        return collect($matches[1])
            ->map(fn (string $id): int => (int) $id)
            ->unique()
            ->values();
    }
}
