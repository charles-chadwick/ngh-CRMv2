<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $users = User::select('id', 'first_name', 'last_name', 'email')
        ->with('company:id,title')
        ->withLastLogin()
        ->paginate(15);

    return Inertia::render('Home', [
        'users' => $users
    ]);
});
