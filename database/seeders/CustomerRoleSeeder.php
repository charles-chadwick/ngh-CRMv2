<?php

namespace Database\Seeders;

use App\Models\CustomerRole;
use Illuminate\Database\Seeder;

class CustomerRoleSeeder extends Seeder
{
    public function run(): void
    {
        collect(['Owner', 'CEO', 'Sales'])
            ->each(fn (string $name) => CustomerRole::create(['name' => $name]));
    }
}
