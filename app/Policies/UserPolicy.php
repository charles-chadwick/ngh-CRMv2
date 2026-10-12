<?php

namespace App\Policies;

use App\Enum\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Only Admins and Super Admins may create users.
     */
    public function create(User $user): bool
    {
        return $user->role->isAtLeast(UserRole::Admin);
    }

    /**
     * Admins and Super Admins may edit users whose role is no higher than their own.
     */
    public function update(User $user, User $target_user): bool
    {
        return $user->role->isAtLeast(UserRole::Admin)
            && $user->role->isAtLeast($target_user->role);
    }
}
