<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function() {
    return Inertia::render('Home');
});
Route::get('/users', [UserController::class, 'index']);
Route::get('/companies', [CompanyController::class, 'index']);