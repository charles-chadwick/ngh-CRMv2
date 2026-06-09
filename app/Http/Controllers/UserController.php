<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'first_name', 'last_name', 'email')
            ->with('company:id,title')
            ->withAvatar()
            ->withLastLogin()
            ->paginate(15);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }
}
