<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {

    }

    public function edit(Customer $customer) {
        return Inertia::render('Customers/Form', ['customer' => $customer]);
    }
}
