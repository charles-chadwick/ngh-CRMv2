<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\Note;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::select('id', 'company_type_id', 'title', 'description')
            ->with('type:id,name')
            ->withAvatar()
            ->withSearch(request('search'))
            ->withSort(request('sort_by'), request('sort_direction'))
            ->withCount('customers')
            ->paginate()
            ->withQueryString();

        return Inertia::render('Company/Index', ['companies' => $companies]);
    }

    public function show(Company $company)
    {
        $company->load([
            'type',
            'creationActivity.causer',
            'updateActivity.causer',
        ]);

        $customers = $company->customers()
            ->withAvatar()
            ->with('role:id,name')
            ->paginate(5, ['*'], 'customers_page')
            ->withQueryString();

        return Inertia::render('Company/Show', [
            'company' => $company,
            'customers' => $customers,
            'notes' => $company->paginatedNotes(),
            'note_types' => Note::TYPES,
        ]);
    }

    public function edit(Company $company)
    {
        $company->load('type:id,name');

        return Inertia::render('Company/Form', [
            'company' => $company,
            'types' => CompanyType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return redirect()->route('companies.show', $company);
    }
}
