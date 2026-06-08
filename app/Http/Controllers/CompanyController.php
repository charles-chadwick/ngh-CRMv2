<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::select('id', 'title', 'description')
            ->withCount('users')
            ->paginate(5);
        return Inertia::render('Company/Index', ['companies' => $companies]);
    }
}
