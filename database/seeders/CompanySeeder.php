<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyType;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Airplane Parts'],
            ['name' => 'Beeswax!'],
            ['name' => 'Car Parts'],
        ])->each(fn ($company) => CompanyType::create($company));

        Company::factory()
            ->count(100)
            ->create();
    }
}
