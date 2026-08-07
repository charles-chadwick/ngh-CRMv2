<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserTableSeeder::class,
            CompanySeeder::class,
            CustomerRoleSeeder::class,
            TitleSeeder::class,
            CustomerSeeder::class,
            LeadSeeder::class,
            ContactSeeder::class,
            NoteSeeder::class,
            MessageSeeder::class,
            TaskSeeder::class,
        ]);
    }
}
