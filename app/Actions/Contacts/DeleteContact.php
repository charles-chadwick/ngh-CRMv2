<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class DeleteContact
{
    use AsAction;

    /**
     * Delete a contact, promoting a sibling to primary when the deleted
     * contact was its parent's primary.
     * @throws Throwable
     */
    public function handle(Contact $contact): void
    {
        DB::transaction(function () use ($contact): void {
            $was_primary = $contact->is_primary;
            $parent = $contact->contactable;

            $contact->delete();

            if ($was_primary) {
                $next = $parent->contacts()->first();

                if ($next !== null) {
                    SetPrimaryContact::run($next);
                }
            }
        });
    }
}
