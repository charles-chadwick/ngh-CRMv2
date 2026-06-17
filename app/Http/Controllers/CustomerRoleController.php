<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRoleRequest;
use App\Models\CustomerRole;
use Inertia\Inertia;

class CustomerRoleController extends Controller
{
    public function index()
    {
        $customer_roles = CustomerRole::query()
            ->withCount('customers')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Settings/CustomerRoles/Index', [
            'customer_roles' => $customer_roles,
        ]);
    }

    public function create()
    {
        return Inertia::render('Settings/CustomerRoles/Form');
    }

    public function store(CustomerRoleRequest $request)
    {
        CustomerRole::create($request->validated());

        return redirect()->route('settings.customer-roles.index');
    }

    public function edit(CustomerRole $customer_role)
    {
        return Inertia::render('Settings/CustomerRoles/Form', [
            'customer_role' => $customer_role,
        ]);
    }

    public function update(CustomerRoleRequest $request, CustomerRole $customer_role)
    {
        $customer_role->update($request->validated());

        return redirect()->route('settings.customer-roles.index');
    }

    public function destroy(CustomerRole $customer_role)
    {
        $customer_role->delete();

        return redirect()->route('settings.customer-roles.index');
    }
}
