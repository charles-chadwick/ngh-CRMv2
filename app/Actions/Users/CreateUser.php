<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class CreateUser
{
    use AsAction;

    /**
     * Create a user and assign the permission role matching their role column.
     *
     * @param  array{role: string, first_name: string, last_name: string, email: string, password: string}  $attributes
     *
     * @throws Throwable
     */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::create($attributes);

            $user->assignRole($user->role->value);

            return $user;
        });
    }
}
