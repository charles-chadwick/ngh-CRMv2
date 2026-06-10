<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $term = preg_replace('/[^a-zA-Z0-9]/', '', request('search'));
        $users = User::select('id', 'first_name', 'last_name', 'email')
            ->with('company:id,title')
            ->when($term, function ($query) use ($term) {
                $query->where('first_name_normalized', 'like', "{$term}%")
                    ->orWhere('last_name_normalized', 'like', "{$term}%");
            })
            ->withAvatar()
            ->withLastLogin()
            ->paginate(15);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }
}
