<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::resource('users', UserController::class);
Route::resource('companies', CompanyController::class);
Route::resource('customers', CustomerController::class);

Route::post('avatar', [AvatarController::class, 'store'])->name('avatar.store');
Route::delete('avatar', [AvatarController::class, 'destroy'])->name('avatar.destroy');
