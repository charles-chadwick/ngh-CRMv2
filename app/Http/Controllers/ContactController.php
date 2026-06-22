<?php

namespace App\Http\Controllers;

use App\Actions\Contacts\CreateContact;
use App\Actions\Contacts\DeleteContact;
use App\Actions\Contacts\SetPrimaryContact;
use App\Actions\Contacts\UpdateContact;
use App\Http\Requests\ContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

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
     * Store a contact against the given contactable model.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        $contactable = $this->resolveModel(
            $request->validated('model_type'),
            $request->validated('model_id'),
        );

        CreateContact::run(
            $contactable,
            $request->safe()->except(['model_type', 'model_id', 'is_primary']),
            $request->boolean('is_primary'),
        );

        return back();
    }

    /**
     * Update an existing contact.
     */
    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        UpdateContact::run(
            $contact,
            $request->safe()->except(['model_type', 'model_id', 'is_primary']),
            $request->boolean('is_primary'),
        );

        return back();
    }

    /**
     * Remove a contact.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        DeleteContact::run($contact);

        return back();
    }

    /**
     * Promote the given contact to be its parent's primary contact.
     */
    public function setPrimary(Contact $contact): RedirectResponse
    {
        SetPrimaryContact::run($contact);

        return back();
    }

    /**
     * Resolve the target-contactable model from the whitelist.
     */
    protected function resolveModel(string $type, int|string $id): Model
    {
        return $this->models[$type]::findOrFail($id);
    }
}
