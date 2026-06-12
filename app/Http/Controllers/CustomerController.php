<?php

namespace App\Http\Controllers;

use App\Enum\CustomerRole;
use App\Models\Customer;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index() {}

    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/Form', [
            'customer' => $customer,
            'roles' => CustomerRole::cases(),
        ]);
    }

    public function update(Customer $customer)
    {
        $customer->update(request()->all());

        return redirect()->route('customers.show', $customer);
    }

    public function show(Customer $customer)
    {
        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }
}
