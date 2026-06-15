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
            ->paginate();

        return Inertia::render('Company/Index', ['companies' => $companies]);
    }

    public function show(Company $company)
    {
        $company->load(['type', 'customers.media', 'customers']);

        return Inertia::render('Company/Show', ['company' => $company]);
    }
}
