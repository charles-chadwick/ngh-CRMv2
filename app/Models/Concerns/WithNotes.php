<?php

namespace App\Models\Concerns;

use App\Models\Note;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds a polymorphic `notes` relationship backed by the {@see Note} model.
 */
trait WithNotes
{
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    /**
     * Paginate the model's notes for display, preserving the current query string.
     *
     * @return LengthAwarePaginator<int, Note>
     */
    public function paginatedNotes(int $perPage = 2): LengthAwarePaginator
    {
        return $this->notes()
            ->paginate($perPage, ['id', 'type', 'title', 'content', 'created_at'], 'notes_page')
            ->withQueryString();
    }
}
