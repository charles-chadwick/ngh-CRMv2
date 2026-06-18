<?php

namespace App\Http\Controllers;

use App\Enum\UserRole;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {

        $users = User::query()
            ->withAvatar()
            ->withLastLogin()
            ->withSearch(request('search'))
            ->paginate(15);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    public function show(User $user)
    {

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        $user->load(['media' => $user->avatarMediaConstraint()]);

        return Inertia::render('Users/Form', [
            'user' => $user,
            'roles' => UserRole::options(),
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.show', $user);
    }
}
