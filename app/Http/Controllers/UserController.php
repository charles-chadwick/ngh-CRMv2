<?php

namespace App\Http\Controllers;

use App\Enum\UserRole;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Search team members that may be @mentioned in a note.
     */
    public function mentionable(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $users = User::query()
            ->whereKeyNot($request->user()?->id)
            ->when($search !== '', fn ($query) => $query->where(
                fn ($grouped) => $grouped->withSearch($search)
            ))
            ->withAvatar()
            ->limit(10)
            ->get();

        return response()->json(
            $users->map(fn (User $user): array => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'avatar_url' => $user->avatar_url,
            ])->values()
        );
    }

    public function index()
    {

        $users = User::query()
            ->withAvatar()
            ->withLastLogin()
            ->withSearch(request('search'))
            ->withSort(request('sort_by'), request('sort_direction'))
            ->paginate(15)
            ->withQueryString();

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
