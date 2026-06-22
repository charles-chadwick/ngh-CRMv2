<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class CreateContact
{
    use AsAction;

    /**
     * Create a contact for the given contactable model, promoting it to
     * primary when requested or when it is the parent's only contact.
     *
     * @param  array<string, mixed>  $attributes
     * @throws Throwable
     */
    public function handle(Model $contactable, array $attributes, bool $is_primary = false): Contact
    {
        return DB::transaction(function () use ($contactable, $attributes, $is_primary): Contact {
            $contact = $contactable->contacts()->create($attributes);

            if ($is_primary || $contactable->contacts()->count() === 1) {
                SetPrimaryContact::run($contact);
            }

            return $contact;
        });
    }
}
