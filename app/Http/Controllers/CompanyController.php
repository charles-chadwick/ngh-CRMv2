<?php

namespace App\Http\Controllers;

use App\Models\Company;
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
            ->paginate();

        return Inertia::render('Company/Index', ['companies' => $companies]);
    }

    public function show(Company $company)
    {
        $company->load([
            'type',
            'customers' => fn ($query) => $query->withAvatar()->with('role:id,name'),
        ]);

        return Inertia::render('Company/Show', ['company' => $company]);
    }
}
