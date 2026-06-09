<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::select('id', 'title', 'description')
            ->withAvatar()
            ->withCount('users')
            ->paginate(5);

        return Inertia::render('Company/Index', ['companies' => $companies]);
    }
}
