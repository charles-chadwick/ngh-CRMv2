<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class UpdateContact
{
    use AsAction;

    /**
     * Update an existing contact, optionally promoting it to primary.
     *
     * @param  array<string, mixed>  $attributes
     * @throws Throwable
     */
    public function handle(Contact $contact, array $attributes, bool $is_primary = false): Contact
    {
        return DB::transaction(function () use ($contact, $attributes, $is_primary): Contact {
            $contact->update($attributes);

            if ($is_primary) {
                SetPrimaryContact::run($contact);
            }

            return $contact;
        });
    }
}
