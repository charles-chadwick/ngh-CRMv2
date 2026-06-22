<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class SetPrimaryContact
{
    use AsAction;

    /**
     * Promote the given contact to be its parent's primary contact,
     * demoting every sibling in the process.
     * @throws Throwable
     */
    public function handle(Contact $contact): Contact
    {
        return DB::transaction(function () use ($contact): Contact {
            $contact->contactable->contacts()
                ->whereKeyNot($contact->getKey())
                ->update(['is_primary' => false]);

            $contact->update(['is_primary' => true]);

            return $contact;
        });
    }
}
