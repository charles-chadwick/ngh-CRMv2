<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        echo "\nAdding Notes\n";

        Customer::all()->each(function (Customer $customer): void {
            $created_by_user = User::inRandomOrder()->first();
            $created_at = fake()->dateTimeBetween($created_by_user->created_at, '-1 year');
            CauserResolver::setCauser($created_by_user);
            Note::factory(rand(0, 5))->forNotable($customer)->create(['created_at' => $created_at, 'updated_at' => $created_at]);
            echo '.';
        });

        Company::all()->each(function (Company $company): void {
            $created_by_user = User::inRandomOrder()->first();
            $created_at = fake()->dateTimeBetween($created_by_user->created_at, '-1 year');
            CauserResolver::setCauser($created_by_user);
            Note::factory(rand(0, 5))->forNotable($company)->create(['created_at' => $created_at, 'updated_at' => $created_at]);
            echo '.';
        });

        echo "\n";
    }
}
