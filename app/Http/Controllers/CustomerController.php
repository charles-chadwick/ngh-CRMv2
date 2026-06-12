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
}
