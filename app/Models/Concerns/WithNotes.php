<?php

namespace App\Models\Concerns;

use App\Models\Note;
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
}
