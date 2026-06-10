<?php

namespace App\Http\Controllers;

use App\Enum\UserRole;
use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $term = preg_replace('/[^a-zA-Z0-9]/', '', request('search'));

        $users = User::select('id', 'role', 'first_name', 'last_name', 'email')
            ->isNotCustomer()
            ->withAvatar()
            ->withLastLogin()
            ->when($term, function ($query) use ($term) {
                $query->where('first_name_normalized', 'like', "{$term}%")
                    ->orWhere('last_name_normalized', 'like', "{$term}%")
                    ->orWhere('email', 'like', "{$term}%")
                    ->orWhere('role', 'like', "{$term}%");
            })
            ->paginate(15);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    public function show(User $user) {

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }
    public function edit(User $user) {

        return $user;
    }
}
