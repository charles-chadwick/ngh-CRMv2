<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Support\CauserResolver;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        echo "\nAdding Leads\n";

        Company::all()->each(function (Company $company): void {
            $created_by_user = User::inRandomOrder()->first();
            $created_at = fake()->dateTimeBetween($created_by_user->created_at);

            // Attribute this lead's creation activity to a random user.
            app(CauserResolver::class)->setCauser($created_by_user);

            Lead::factory(rand(0, 5))->create([
                'company_id' => $company->id,
                'title' => fn (): string => RickAndMortyDialogue::next(),
                'assigned_to' => fn (): ?int => User::inRandomOrder()->value('id'),
                'created_at' => $created_at,
                'updated_at' => $created_at,
            ]);

            echo '.';
        });

        // Clear the causer so it doesn't leak into subsequent seeders.
        app(CauserResolver::class)->setCauser(null);

        echo "\n";
    }
}
