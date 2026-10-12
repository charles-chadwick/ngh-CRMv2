<?php

namespace App\Http\Controllers;

use App\Actions\Customers\CreateCustomer;
use App\Enum\TaskStatus;
use App\Enum\TaskType;
use App\Http\Requests\CustomerRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerRole;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(Request $request)
    {
        return Inertia::render('Customers/Form', [
            'customer' => null,
            'company_id' => $request->integer('company_id') ?: null,
            'companies' => Company::orderBy('title')->get(['id', 'title']),
            'roles' => CustomerRole::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = CreateCustomer::run($request->validated());

        return redirect()->route('customers.show', $customer);
    }

    public function edit(Customer $customer)
    {
        $customer->load('company:id,title', 'role:id,name');

        return Inertia::render('Customers/Form', [
            'customer' => $customer,
            'companies' => Company::orderBy('title')->get(['id', 'title']),
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

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return back();
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'company:id,title',
            'role:id,name',
            'media' => $customer->avatarMediaConstraint(),
            'creationActivity.causer',
            'updateActivity.causer',
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'notes' => $customer->paginatedNotes(),
            'note_types' => Note::TYPES,
            'contacts' => $customer->paginatedContacts(),
            'contact_types' => Contact::TYPES,
            'messages' => $customer->paginatedMessages(),
            'tasks' => $customer->paginatedTasks(),
            'task_types' => TaskType::options(),
            'task_statuses' => TaskStatus::options(),
        ]);
    }
}
