<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    /**
     * Whitelist of models that may receive contacts, keyed by the
     * `model_type` value accepted from the client.
     *
     * @var array<string, class-string<Model>>
     */
    protected array $models = [
        'customer' => Customer::class,
        'company' => Company::class,
    ];

    /**
     * Fields copied straight from the request onto a contact.
     *
     * @var list<string>
     */
    protected array $fields = [
        'type',
        'street_address',
        'city',
        'state',
        'zip',
        'work_phone',
        'mobile_phone',
    ];

    /**
     * Store a contact against the given contactable model.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $model = $this->resolveModel($validated['model_type'], $validated['model_id']);

        DB::transaction(function () use ($request, $model): void {
            $contact = $model->contacts()->create($request->safe()->only($this->fields));

            $should_be_primary = $request->boolean('is_primary')
                || $model->contacts()->count() === 1;

            if ($should_be_primary) {
                $this->makePrimary($contact);
            }
        });

        return back();
    }

    /**
     * Update an existing contact.
     */
    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        DB::transaction(function () use ($request, $contact): void {
            $contact->update($request->safe()->only($this->fields));

            if ($request->boolean('is_primary')) {
                $this->makePrimary($contact);
            }
        });

        return back();
    }

    /**
     * Remove a contact.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return back();
    }

    /**
     * Promote the given contact to be its parent's primary contact.
     */
    public function setPrimary(Contact $contact): RedirectResponse
    {
        DB::transaction(fn () => $this->makePrimary($contact));

        return back();
    }

    /**
     * Make the contact primary and demote its siblings.
     */
    protected function makePrimary(Contact $contact): void
    {
        $contact->contactable->contacts()
            ->whereKeyNot($contact->getKey())
            ->update(['is_primary' => false]);

        $contact->update(['is_primary' => true]);
    }

    /**
     * Resolve the target contactable model from the whitelist.
     */
    protected function resolveModel(string $type, int|string $id): Model
    {
        return $this->models[$type]::findOrFail($id);
    }
}
