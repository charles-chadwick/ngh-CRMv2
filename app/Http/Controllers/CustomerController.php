<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\CustomerRole;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::query()
            ->with(['company:id,title', 'role:id,name'])
            ->withAvatar()
            ->withSearch(request('search'))
            ->paginate(15);

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
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }
}
