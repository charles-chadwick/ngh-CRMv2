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
            ->withCount('customers')
            ->withSearch(request('search'))
            ->withSort(request('sort_by'), request('sort_direction'))
            ->paginate()
            ->withQueryString();

        return Inertia::render('Company/Index', ['companies' => $companies]);
    }

    public function show(Company $company)
    {
        $company->load([
            'type',
            'customers' => fn ($query) => $query->withAvatar()->with('role:id,name'),
        ]);

        return Inertia::render('Company/Show', [
            'company' => $company,
            'notes' => $company->notes()
                ->paginate(5, ['id', 'type', 'title', 'content', 'created_at'], 'notes_page')
                ->withQueryString(),
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
