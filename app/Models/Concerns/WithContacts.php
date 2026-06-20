<?php

namespace App\Models\Concerns;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Adds a polymorphic `contacts` relationship backed by the {@see Contact} model.
 */
trait WithContacts
{
    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable')
            ->orderByDesc('is_primary')
            ->latest();
    }

    public function primaryContact(): MorphOne
    {
        return $this->morphOne(Contact::class, 'contactable')
            ->where('is_primary', true);
    }
}
