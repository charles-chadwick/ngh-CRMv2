<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Note;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        echo "\nAdding Notes\n";

        Customer::all()->each(function (Customer $customer): void {
            Note::factory(rand(0, 5))->forNotable($customer)->create();
            echo '.';
        });

        Company::all()->each(function (Company $company): void {
            Note::factory(rand(0, 5))->forNotable($company)->create();
            echo '.';
        });

        echo "\n";
    }
}
