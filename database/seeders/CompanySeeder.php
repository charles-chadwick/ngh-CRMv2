<?php

namespace Database\Seeders;

use App\Enum\UserRole;
use App\Models\Company;
use App\Models\CompanyType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

use function fake;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Airplane Parts'],
            ['name' => 'Beeswax!'],
            ['name' => 'Car Parts'],
        ])->each(fn ($company) => CompanyType::create($company));

        $super_admins = User::where('role', UserRole::SuperAdmin->value)->get();

        for ($index = 0; $index < 100; $index++) {
            $created_by_user = $super_admins->random();
            $created_at = fake()->dateTimeBetween($created_by_user->created_at, 'yesterday');

            // Attribute this company's creation activity to a random Super Admin.
            app(CauserResolver::class)->setCauser($created_by_user);

            Company::factory()->create([
                'created_at' => $created_at,
                'updated_at' => $created_at,
            ]);
        }

        // Clear the causer so it doesn't leak into subsequent seeders.
        app(CauserResolver::class)->setCauser(null);
    }
}
