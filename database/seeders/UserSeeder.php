<?php

namespace Database\Seeders;

use App\Models\Login;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run() : void
    {
        User::factory(50)->create()->each(function ($user) {
            Login::factory(500)->create(['user_id' => $user->id]);
        });
    }
}
