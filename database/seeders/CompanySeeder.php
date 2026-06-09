<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::factory()->count(100)->create();

        User::all()->each(function (User $user) use ($companies) {
            $user->company_id = $companies->random()->id;
            $user->save();
        });
    }
}
