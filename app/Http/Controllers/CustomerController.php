<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerRole;
use App\Models\Note;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::query()
            ->with([
                'company' => fn ($query) => $query->select('id', 'title')->withAvatar(),
                'role:id,name',
            ])
            ->withAvatar()
            ->withSearch(request('search'))
            ->withSort(request('sort_by'), request('sort_direction'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function edit(Customer $customer)
    {
        $customer->load('company:id,title', 'role:id,name');

        return Inertia::render('Customers/Form', [
            'customer' => $customer,
            'roles' => CustomerRole::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $customer->update($validated);

        return redirect()->route('customers.show', $customer);
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'company:id,title',
            'role:id,name',
            'media' => $customer->avatarMediaConstraint(),
            'creationActivity.causer',
            'updateActivity.causer',
            'contacts',
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'notes' => $customer->paginatedNotes(),
            'note_types' => Note::TYPES,
            'contacts' => $customer->contacts,
            'contact_types' => Contact::TYPES,
        ]);
    }
}
